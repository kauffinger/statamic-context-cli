<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class StatamicContextSearchCommand extends AbstractSearchCommand
{
    protected $signature = 'statamic-context:docs:search
                            {query? : Search query term (e.g. collections, blueprints)}
                            {--limit=10 : Maximum number of results to display}
                            {--start=0 : Starting position for pagination}
                            {--interactive : Use interactive prompts}';

    protected $description = 'Search through Statamic documentation';

    protected function getDocumentationType(): string
    {
        return 'Statamic';
    }

    protected function getUpdateCommand(): string
    {
        return 'statamic-context:docs:update';
    }

    protected function getSearchPlaceholders(): string
    {
        return 'e.g. "collections", "blueprints", "templating"';
    }

    protected function getInteractiveTitle(): string
    {
        return 'Statamic Context CLI';
    }

    protected function getNoDocsMessage(): string
    {
        return 'No documentation found.';
    }

    protected function displayExamples(string $commandName): void
    {
        $this->line("  php artisan {$commandName} collections");
        $this->line("  php artisan {$commandName} blueprints --limit=20");
        $this->line("  php artisan {$commandName} \"extend markdown\" --start=10");
    }
}
