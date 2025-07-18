<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Exception;
use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Services\DocumentationFetcher;

abstract class AbstractUpdateCommand extends Command
{
    abstract protected function getDocumentationType(): string;

    abstract protected function getConfigKey(): string;

    public function handle(DocumentationFetcher $fetcher): int
    {
        $this->components->info('🚀 Updating '.$this->getDocumentationType().' documentation from GitHub...');

        try {
            $startTime = microtime(true);

            // Pass the command instance to fetcher for progress updates
            $stats = $fetcher->fetchAll($this->getConfigKey(), $this);

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->components->success("✅ {$this->getDocumentationType()} documentation update completed in {$duration} seconds!");

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
            $this->components->error('❌ Failed to update '.$this->getDocumentationType().' documentation: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
