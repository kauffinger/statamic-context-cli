<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Exception;
use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Services\PeakDocumentationService;

class UpdatePeakDocsCommand extends Command
{
    protected $signature = 'statamic-context:peak:update
                            {--force : Force update all Peak documentation}';

    protected $description = 'Fetch and update Statamic Peak documentation from GitHub';

    public function handle(): int
    {
        $this->components->info('🚀 Updating Peak documentation from GitHub...');

        try {
            $startTime = microtime(true);

            $service = new PeakDocumentationService;
            $stats = $service->fetchAll($this);

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->components->success("✅ Peak documentation update completed in {$duration} seconds!");

            $this->components->twoColumnDetail('📁 Total files processed', (string) $stats['total']);
            $this->components->twoColumnDetail('📝 Files updated', (string) $stats['updated']);

            if ($stats['errors'] > 0) {
                $this->components->twoColumnDetail(
                    '<fg=yellow>⚠️  Errors encountered</>',
                    "<fg=yellow>{$stats['errors']}</>"
                );
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->components->error('❌ Failed to update Peak documentation: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
