<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Exception;
use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Services\DocumentationFetcher;
use Symfony\Component\Console\Attribute\AsCommand;

class UpdateDocsCommand extends Command
{
    protected $signature = 'statamic-context-docs:update
                            {--force : Force update all documentation}';

    protected $description = 'Fetch and update Statamic documentation from GitHub';

    public function handle(DocumentationFetcher $fetcher): int
    {
        $this->components->info('Fetching Statamic documentation from GitHub...');

        try {
            $startTime = microtime(true);

            $stats = $fetcher->fetchAll();

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->components->success("Documentation update completed in {$duration} seconds!");

            $this->components->twoColumnDetail('Total files processed', (string) $stats['total']);
            $this->components->twoColumnDetail('Files updated', (string) $stats['updated']);

            if ($stats['errors'] > 0) {
                $this->components->twoColumnDetail(
                    '<fg=yellow>Errors encountered</>',
                    "<fg=yellow>{$stats['errors']}</>"
                );
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->components->error('Failed to update documentation: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
