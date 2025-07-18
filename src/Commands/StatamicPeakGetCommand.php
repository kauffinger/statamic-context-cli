<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class StatamicPeakGetCommand extends AbstractGetCommand
{
    protected $signature = 'statamic-context:peak:get
                            {id : The ID of the Peak documentation entry to retrieve}
                            {--format=text : Output format (text, json)}';

    protected $description = 'Retrieve a specific Peak documentation entry by ID';

    protected function getDocumentationType(): string
    {
        return 'Peak';
    }

    protected function getUpdateCommand(): string
    {
        return 'statamic-context:peak:update';
    }

    protected function getNoDocsMessage(): string
    {
        return 'No Peak documentation found. Run statamic-context:peak:update first.';
    }

    protected function getNotFoundMessage(string $id): string
    {
        return "Peak documentation entry with ID '{$id}' not found.";
    }

    protected function getEntryTitlePrefix(): string
    {
        return 'Peak Documentation Entry';
    }
}
