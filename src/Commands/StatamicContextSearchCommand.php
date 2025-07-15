<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Exceptions\DocumentationException;
use StatamicContext\StatamicContext\Models\Documentation;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class StatamicContextSearchCommand extends Command
{
    protected $signature = 'statamic-context:docs:search
                            {query? : Search query term (e.g. collections, blueprints)}
                            {--limit=10 : Maximum number of results to display}
                            {--interactive : Use interactive prompts}';

    protected $description = 'Search through Statamic documentation';

    public function handle(DocumentationRepository $repository): int
    {
        try {
            if ($this->option('interactive')) {
                return $this->handleInteractive($repository);
            }

            // Check if query was provided as argument
            $query = $this->argument('query');
            
            if ($query) {
                // Direct search with provided query
                $this->searchDocumentation($query, $repository);
                return self::SUCCESS;
            }

            // Show help and prompt for query if none provided
            $this->displayHelp($repository);
            $query = $this->promptForQuery();

            if ($query === '') {
                return self::SUCCESS;
            }

            $this->searchDocumentation($query, $repository);

            return self::SUCCESS;
        } catch (DocumentationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function handleInteractive(DocumentationRepository $repository): int
    {
        $this->components->info('🔍 Statamic Context CLI - Interactive Search');

        if (! $repository->exists()) {
            $this->components->warn('No documentation found.');
            $shouldUpdate = confirm('Would you like to update the documentation first?');

            if ($shouldUpdate) {
                $this->components->info('Run: php artisan docs:update');

                return self::SUCCESS;
            }
        }

        while (true) {
            $action = select(
                'What would you like to do?',
                [
                    'search' => 'Search documentation',
                    'status' => 'View documentation status',
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
            placeholder: 'e.g. "collections", "blueprints", "templating"',
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
            placeholder: 'e.g. "collections", "blueprints", "templating"',
            required: false
        );
    }

    private function displayHelp(DocumentationRepository $repository): void
    {
        $this->components->info('Statamic Context CLI - Search through Statamic documentation');

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Usage</>', 'php artisan statamic-context:docs:search [query]');
        $this->components->twoColumnDetail('<fg=gray>Interactive</>', 'php artisan statamic-context:docs:search --interactive');
        $this->components->twoColumnDetail('<fg=gray>Update docs</>', 'php artisan docs:update');

        $this->newLine();
        $this->line('<fg=yellow>Examples:</>');
        $this->line('  php artisan statamic-context:docs:search collections');
        $this->line('  php artisan statamic-context:docs:search blueprints');
        $this->line('  php artisan statamic-context:docs:search templating');

        $this->newLine();
        $this->displayStatus($repository);
    }

    private function displayStatus(DocumentationRepository $repository): void
    {
        if ($repository->exists()) {
            $count = $repository->count();
            $this->components->twoColumnDetail(
                '<fg=green>Documentation status</>',
                "<fg=green>{$count} documents available</>"
            );
        } else {
            $this->components->warn('No documentation found. Run docs:update first.');
        }
    }

    private function searchDocumentation(string $query, DocumentationRepository $repository): void
    {
        throw_unless($repository->exists(), DocumentationException::indexNotFound());

        $this->components->task("Searching for '{$query}'", function () use ($query, $repository, &$results) {
            $results = $repository->search($query);

            return true;
        });

        if ($results->isEmpty()) {
            $this->components->warn('No results found.');
            $this->newLine();
            $this->line('Try different search terms or update the documentation:');
            $this->line('  <fg=gray>php artisan docs:update</>');

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
        $total = $results->count();
        $showing = min($limit, $total);

        $this->newLine();
        $this->components->info("Found {$total} results".($total > $limit ? " (showing {$showing})" : ''));
        $this->newLine();

        $results->take($limit)->each(function ($doc) use ($query) {
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

        if ($total > $limit) {
            $this->components->info('Use --limit option to see more results');

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
        $this->components->info('Press Enter to continue...');
        $this->ask('');
    }

    private function getSnippet(string $content, string $query): ?string
    {
        $lines = collect(explode("\n", $content));
        $queryLower = strtolower($query);

        // Find line containing the query
        $matchingLine = $lines->first(fn ($line) => str_contains(strtolower($line), $queryLower));

        if ($matchingLine) {
            return Str::limit(trim($matchingLine), 80);
        }

        // Return first meaningful line if no direct match
        return $lines
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '#'))
            ->first();
    }
}
