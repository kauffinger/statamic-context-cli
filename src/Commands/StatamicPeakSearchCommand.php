<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Exceptions\DocumentationException;
use StatamicContext\StatamicContext\Models\Documentation;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class StatamicPeakSearchCommand extends Command
{
    protected $signature = 'statamic-context:peak:search
                            {query? : Search query term (e.g. page-builder, seo, tooling)}
                            {--limit=10 : Maximum number of results to display}
                            {--start=0 : Starting position for pagination}
                            {--interactive : Use interactive prompts}';

    protected $description = 'Search through Statamic Peak documentation';

    public function __construct(private readonly DocumentationRepository $repository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            if ($this->option('interactive')) {
                return $this->handleInteractive($this->repository);
            }

            // Check if query was provided as argument
            $query = $this->argument('query');

            if ($query) {
                // Direct search with provided query
                $this->searchDocumentation($query, $this->repository);

                return self::SUCCESS;
            }

            // Show help and prompt for query if none provided
            $this->displayHelp($this->repository);
            $query = $this->promptForQuery();

            if ($query === '') {
                return self::SUCCESS;
            }

            $this->searchDocumentation($query, $this->repository);

            return self::SUCCESS;
        } catch (DocumentationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function handleInteractive(DocumentationRepository $repository): int
    {
        $this->components->info('🔍 Statamic Peak Context CLI - Interactive Search');

        if (! $repository->exists()) {
            $this->components->warn('No Peak documentation found.');
            $shouldUpdate = confirm('Would you like to update the Peak documentation first?');

            if ($shouldUpdate) {
                $this->components->info('Run: php artisan statamic-context:peak:update');

                return self::SUCCESS;
            }
        }

        while (true) {
            $action = select(
                'What would you like to do?',
                [
                    'search' => 'Search Peak documentation',
                    'status' => 'View Peak documentation status',
                    'exit' => 'Exit',
                ]
            );

            if ($action === 'exit') {
                break;
            }

            match ($action) {
                'search' => $this->handleSearchFlow($repository),
                'status' => $this->displayStatus($repository),
                default => null
            };

            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function handleSearchFlow(DocumentationRepository $repository): void
    {
        $query = text(
            label: 'Enter your search query:',
            placeholder: 'e.g. "page-builder", "seo", "tooling", "getting-started"',
            required: true
        );

        if ($query !== '' && $query !== '0') {
            $this->searchDocumentation($query, $repository);
        }
    }

    private function promptForQuery(): string
    {
        return text(
            label: 'Enter your search query (or press Enter to exit):',
            placeholder: 'e.g. "page-builder", "seo", "tooling", "getting-started"',
            required: false
        );
    }

    private function displayHelp(DocumentationRepository $repository): void
    {
        $this->components->info('Statamic Peak Context CLI - Search through Statamic Peak documentation');

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Usage</>', 'php artisan statamic-context:peak:search [query]');
        $this->components->twoColumnDetail('<fg=gray>Interactive</>', 'php artisan statamic-context:peak:search --interactive');
        $this->components->twoColumnDetail('<fg=gray>Pagination</>', 'php artisan statamic-context:peak:search [query] --start=10 --limit=10');
        $this->components->twoColumnDetail('<fg=gray>Update docs</>', 'php artisan statamic-context:peak:update');

        $this->newLine();
        $this->line('<fg=yellow>Examples:</>');
        $this->line('  php artisan statamic-context:peak:search page-builder');
        $this->line('  php artisan statamic-context:peak:search seo --limit=20');
        $this->line('  php artisan statamic-context:peak:search "browser appearance" --start=10');

        $this->newLine();
        $this->displayStatus($repository);
    }

    private function displayStatus(DocumentationRepository $repository): void
    {
        if ($repository->exists()) {
            $count = $repository->count();
            $this->components->twoColumnDetail(
                '<fg=green>Peak documentation status</>',
                "<fg=green>{$count} documents available</>"
            );
        } else {
            $this->components->warn('No Peak documentation found. Run statamic-context:peak:update first.');
        }
    }

    private function searchDocumentation(string $query, DocumentationRepository $repository): void
    {
        throw_unless($repository->exists(), DocumentationException::indexNotFound());

        $this->components->task("Searching Peak docs for '{$query}'", function () use ($query, $repository, &$results) {
            $results = $repository->search($query);

            return true;
        });

        if ($results->isEmpty()) {
            $this->components->warn('No results found.');
            $this->newLine();
            $this->line('Try different search terms or update the documentation:');
            $this->line('  <fg=gray>php artisan statamic-context:peak:update</>');

            return;
        }

        $this->displayResults($results, $query);
    }

    /**
     * @param  Collection<int, Documentation>  $results
     */
    private function displayResults(Collection $results, string $query = '', ?int $customLimit = null): void
    {
        $limit = $customLimit ?? (int) $this->option('limit');
        $start = (int) $this->option('start');
        $total = $results->count();

        // Apply pagination
        $paginatedResults = $results->slice($start, $limit);
        $showing = $paginatedResults->count();
        $end = min($start + $limit, $total);

        $this->newLine();
        if ($start > 0) {
            $this->components->info("Found {$total} results (showing {$start}-".($end - 1).')');
        } else {
            $this->components->info("Found {$total} results".($total > $limit ? " (showing {$showing})" : ''));
        }
        $this->newLine();

        $paginatedResults->each(function ($doc) use ($query) {
            $this->components->twoColumnDetail(
                "<fg=cyan>{$doc->title}</>",
                "<fg=gray>{$doc->collection}</>"
            );

            $this->components->twoColumnDetail(
                "<fg=yellow>ID: {$doc->getId()}</>",
                ''
            );

            if ($doc->content) {
                $snippet = $this->getSnippet($doc->content, $query);
                if ($snippet) {
                    $this->line("  <fg=gray>{$snippet}</>");
                }
            }

            $this->line("  <fg=blue;options=underscore>{$doc->githubUrl}</>");
            $this->newLine();
        });

        // Show pagination info
        if ($total > $end) {
            $nextStart = $start + $limit;
            $this->components->info("Use --start={$nextStart} to see the next page");

            if ($this->option('interactive')) {
                $showMore = confirm('Would you like to see more results?');
                if ($showMore) {
                    $this->displayResults($results, $query, $total);
                }
            }
        }

        // Add entry selection option in interactive mode
        if ($this->option('interactive') && $results->isNotEmpty()) {
            $this->handleEntrySelection($results);
        }
    }

    /**
     * @param  Collection<int, Documentation>  $results
     */
    private function handleEntrySelection(Collection $results): void
    {
        $this->newLine();
        $selectEntry = confirm('Would you like to view the full content of a specific entry?');

        if (! $selectEntry) {
            return;
        }

        // Use search prompt for large result sets, select for small ones
        if ($results->count() > 10) {
            $selectedIndex = search(
                'Search for an entry to view full content:',
                fn (string $value) => $results
                    ->filter(fn ($doc) => str_contains(strtolower("{$doc->title} {$doc->collection}"), strtolower($value)))
                    ->mapWithKeys(fn ($doc, $index) => [$index => "{$doc->title} ({$doc->collection})"])
                    ->toArray()
            );

            if (! is_numeric($selectedIndex)) {
                return;
            }

            $selectedDoc = $results->get((int) $selectedIndex);
        } else {
            // Create options for selection
            $options = $results->mapWithKeys(function ($doc, $index) {
                $key = (string) $index;
                $value = "{$doc->title} ({$doc->collection})";

                return [$key => $value];
            })->toArray();

            // Add exit option
            $options['exit'] = 'Back to search';

            $selectedIndex = select(
                'Select an entry to view full content:',
                $options
            );

            if ($selectedIndex === 'exit') {
                return;
            }

            $selectedDoc = $results->get((int) $selectedIndex);
        }

        if ($selectedDoc) {
            $this->displayFullContent($selectedDoc);
        }
    }

    private function displayFullContent(Documentation $doc): void
    {
        $this->newLine();
        $this->components->info("Full content: {$doc->title}");
        $this->newLine();

        $this->components->twoColumnDetail('<fg=cyan>Title</>', $doc->title);
        $this->components->twoColumnDetail('<fg=cyan>Collection</>', $doc->collection);
        $this->components->twoColumnDetail('<fg=cyan>GitHub URL</>', $doc->githubUrl);
        $this->newLine();

        if ($doc->content) {
            $this->line('<fg=yellow>Content:</>');
            $this->newLine();
            $this->line($doc->content);
        } else {
            $this->components->warn('No content available for this entry.');
        }

        $this->newLine();
        pause('Press Enter to continue...');
    }

    private function getSnippet(string $content, string $query): ?string
    {
        // Remove frontmatter, special characters, and clean up content
        $cleanContent = $this->cleanContentForSnippet($content);
        $lines = collect(explode("\n", $cleanContent));
        $queryLower = strtolower($query);

        // For multi-word queries, try to find lines with any of the words
        $queryWords = preg_split('/\s+/', $queryLower);
        $matchingLines = collect();

        // Find lines containing query words
        $lines->each(function ($line) use ($queryWords, $queryLower, &$matchingLines) {
            $lineLower = strtolower($line);
            // Prioritize exact phrase matches
            if ($queryLower && str_contains($lineLower, $queryLower)) {
                $matchingLines->prepend($line);
            } elseif (count($queryWords) > 1) {
                // For multi-word queries, check if line contains any word
                foreach ($queryWords as $word) {
                    if (str_contains($lineLower, $word)) {
                        $matchingLines->push($line);
                        break;
                    }
                }
            }
        });

        if ($matchingLines->isNotEmpty()) {
            $snippet = trim($matchingLines->first());

            return Str::limit($snippet, 200, '...');
        }

        // Return first meaningful line if no direct match
        $firstMeaningful = $lines
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => strlen($line) > 20) // Skip very short lines
            ->first();

        return $firstMeaningful ? Str::limit($firstMeaningful, 200, '...') : null;
    }

    private function cleanContentForSnippet(string $content): string
    {
        // Remove YAML frontmatter
        $content = preg_replace('/^---[\s\S]*?---\s*/m', '', $content);

        // Remove markdown headers (but keep the text)
        $content = preg_replace('/^#+\s*/m', '', (string) $content);

        // Remove markdown links but keep text
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', (string) $content);

        // Remove code fences
        $content = preg_replace('/```[\s\S]*?```/', '', (string) $content);
        $content = preg_replace('/`([^`]+)`/', '$1', (string) $content);

        // Remove HTML tags
        $content = strip_tags((string) $content);

        // Clean up special characters and excessive whitespace
        $content = preg_replace('/[*_~\[\](){}#]/', '', $content);
        $content = preg_replace('/\s+/', ' ', (string) $content);
        $content = preg_replace('/\n{3,}/', "\n\n", (string) $content);

        return trim((string) $content);
    }
}
