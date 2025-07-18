<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;

class RebuildIndexCommand extends Command
{
    protected $signature = 'statamic-context:docs:rebuild-index
                            {--docs : Rebuild Statamic docs index}
                            {--peak : Rebuild Peak docs index}
                            {--all : Rebuild all indexes}';

    protected $description = 'Rebuild search index with content for better performance';

    public function handle(): int
    {
        $rebuildDocs = $this->option('docs') || $this->option('all');
        $rebuildPeak = $this->option('peak') || $this->option('all');

        if (! $rebuildDocs && ! $rebuildPeak) {
            $this->components->info('Choose which index to rebuild:');
            $rebuildDocs = $this->confirm('Rebuild Statamic docs index?');
            $rebuildPeak = $this->confirm('Rebuild Peak docs index?');
        }

        if ($rebuildDocs) {
            $this->rebuildIndex('docs.repository', 'Statamic docs');
        }

        if ($rebuildPeak) {
            $this->rebuildIndex('peak_docs.repository', 'Peak docs');
        }

        if (! $rebuildDocs && ! $rebuildPeak) {
            $this->components->warn('No indexes selected for rebuild.');

            return self::SUCCESS;
        }

        $this->components->info('✅ Index rebuild complete! Search should now be faster.');

        return self::SUCCESS;
    }

    private function rebuildIndex(string $repositoryBinding, string $name): void
    {
        /** @var DocumentationRepository $repository */
        $repository = $this->laravel->make($repositoryBinding);

        if (! $repository->exists()) {
            $this->components->warn("{$name} index not found. Run the update command first.");

            return;
        }

        $count = $repository->count();
        $this->components->task("Rebuilding {$name} index ({$count} documents)", function () use ($repository) {
            $repository->rebuildIndexWithContent();

            return true;
        });
    }
}
