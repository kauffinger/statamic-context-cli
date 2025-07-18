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

abstract class AbstractSearchCommand extends Command
{
    public function __construct(private readonly DocumentationRepository $repository)
    {
        parent::__construct();
    }

    abstract protected function getDocumentationType(): string;

    abstract protected function getUpdateCommand(): string;

    abstract protected function getSearchPlaceholders(): string;

    abstract protected function getInteractiveTitle(): string;

    abstract protected function getNoDocsMessage(): string;

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
        $this->components->info('🔍 '.$this->getInteractiveTitle().' - Interactive Search');

        if (! $repository->exists()) {
            $this->components->warn($this->getNoDocsMessage());
            $shouldUpdate = confirm('Would you like to update the '.$this->getDocumentationType().' documentation first?');

            if ($shouldUpdate) {
                $this->components->info('Run: php artisan '.$this->getUpdateCommand());

                return self::SUCCESS;
            }
        }

        while (true) {
            $action = select(
                'What would you like to do?',
                [
                    'search' => 'Search '.$this->getDocumentationType().' documentation',
                    'status' => 'View '.$this->getDocumentationType().' documentation status',
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
            placeholder: $this->getSearchPlaceholders(),
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
            placeholder: $this->getSearchPlaceholders(),
            required: false
        );
    }

    protected function displayHelp(DocumentationRepository $repository): void
    {
        $commandName = $this->getName();
        $this->components->info('Statamic Context CLI - Search through '.$this->getDocumentationType().' documentation');

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Usage</>', "php artisan {$commandName} [query]");
        $this->components->twoColumnDetail('<fg=gray>Interactive</>', "php artisan {$commandName} --interactive");
        $this->components->twoColumnDetail('<fg=gray>Pagination</>', "php artisan {$commandName} [query] --start=10 --limit=10");
        $this->components->twoColumnDetail('<fg=gray>Update docs</>', 'php artisan '.$this->getUpdateCommand());

        $this->newLine();
        $this->line('<fg=yellow>Examples:</>');
        $this->displayExamples($commandName);

        $this->newLine();
        $this->displayStatus($repository);
    }

    abstract protected function displayExamples(string $commandName): void;

    private function displayStatus(DocumentationRepository $repository): void
    {
        if ($repository->exists()) {
            $count = $repository->count();
            $this->components->twoColumnDetail(
                '<fg=green>'.$this->getDocumentationType().' documentation status</>',
                "<fg=green>{$count} documents available</>"
            );
        } else {
            $this->components->warn('No '.$this->getDocumentationType().' documentation found. Run '.$this->getUpdateCommand().' first.');
        }
    }

    private function searchDocumentation(string $query, DocumentationRepository $repository): void
    {
        throw_unless($repository->exists(), DocumentationException::indexNotFound());

        $searchMessage = $this->getDocumentationType() === 'Statamic'
            ? "Searching for '{$query}'"
            : "Searching {$this->getDocumentationType()} docs for '{$query}'";

        $this->components->task($searchMessage, function () use ($query, $repository, &$results) {
            $results = $repository->search($query);

            return true;
        });

        if ($results->isEmpty()) {
            $this->components->warn('No results found.');
            $this->newLine();
            $this->line('Try different search terms or update the documentation:');
            $this->line('  <fg=gray>php artisan '.$this->getUpdateCommand().'</>');

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
