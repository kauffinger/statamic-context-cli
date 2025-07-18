<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

class StatamicContextGetCommand extends AbstractGetCommand
{
    protected $signature = 'statamic-context:docs:get
                            {id : The ID of the documentation entry to retrieve}
                            {--format=text : Output format (text, json)}';

    protected $description = 'Retrieve a specific documentation entry by ID';

    protected function getDocumentationType(): string
    {
        return 'Statamic';
    }

    protected function getUpdateCommand(): string
    {
        return 'update-docs';
    }

    protected function getNoDocsMessage(): string
    {
        return 'No documentation found. Run update-docs first.';
    }

    protected function getNotFoundMessage(string $id): string
    {
        return "Documentation entry with ID '{$id}' not found.";
    }

    protected function getEntryTitlePrefix(): string
    {
        return 'Documentation Entry';
    }
}
