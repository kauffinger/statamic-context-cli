<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class UpdateDocsCommand extends AbstractUpdateCommand
{
    protected $signature = 'statamic-context:docs:update
                            {--force : Force update all documentation}';

    protected $description = 'Fetch and update Statamic documentation from GitHub';

    protected function getDocumentationType(): string
    {
        return 'Statamic';
    }

    protected function getConfigKey(): string
    {
        return 'docs';
    }
}
