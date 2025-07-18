<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class StatamicPeakSearchCommand extends AbstractSearchCommand
{
    protected $signature = 'statamic-context:peak:search
                            {query? : Search query term (e.g. page-builder, seo, tooling)}
                            {--limit=10 : Maximum number of results to display}
                            {--start=0 : Starting position for pagination}
                            {--interactive : Use interactive prompts}';

    protected $description = 'Search through Statamic Peak documentation';

    protected function getDocumentationType(): string
    {
        return 'Peak';
    }

    protected function getUpdateCommand(): string
    {
        return 'statamic-context:peak:update';
    }

    protected function getSearchPlaceholders(): string
    {
        return 'e.g. "page-builder", "seo", "tooling", "getting-started"';
    }

    protected function getInteractiveTitle(): string
    {
        return 'Statamic Peak Context CLI';
    }

    protected function getNoDocsMessage(): string
    {
        return 'No Peak documentation found.';
    }

    protected function displayExamples(string $commandName): void
    {
        $this->line("  php artisan {$commandName} page-builder");
        $this->line("  php artisan {$commandName} seo --limit=20");
        $this->line("  php artisan {$commandName} \"browser appearance\" --start=10");
    }
}
