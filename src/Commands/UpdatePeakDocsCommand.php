<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class UpdatePeakDocsCommand extends AbstractUpdateCommand
{
    protected $signature = 'statamic-context:peak:update
                            {--force : Force update all Peak documentation}';

    protected $description = 'Fetch and update Statamic Peak documentation from GitHub';

    protected function getDocumentationType(): string
    {
        return 'Peak';
    }

    protected function getConfigKey(): string
    {
        return 'peak_docs';
    }
}
