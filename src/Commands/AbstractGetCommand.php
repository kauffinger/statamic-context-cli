<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Exceptions\DocumentationException;
use StatamicContext\StatamicContext\Models\Documentation;

abstract class AbstractGetCommand extends Command
{
    public function __construct(private readonly DocumentationRepository $repository)
    {
        parent::__construct();
    }

    abstract protected function getDocumentationType(): string;

    abstract protected function getUpdateCommand(): string;

    abstract protected function getNoDocsMessage(): string;

    abstract protected function getNotFoundMessage(string $id): string;

    abstract protected function getEntryTitlePrefix(): string;

    public function handle(): int
    {
        try {
            $id = $this->argument('id');
            $format = $this->option('format');

            if (! $this->repository->exists()) {
                $this->components->error($this->getNoDocsMessage());

                return self::FAILURE;
            }

            $doc = $this->repository->findById($id);

            if (! $doc instanceof Documentation) {
                $this->components->error($this->getNotFoundMessage($id));

                return self::FAILURE;
            }

            if ($format === 'json') {
                $this->outputJson($doc);
            } else {
                $this->outputText($doc);
            }

            return self::SUCCESS;
        } catch (DocumentationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function outputJson(Documentation $doc): void
    {
        $data = [
            'id' => $doc->getId(),
            'title' => $doc->title,
            'collection' => $doc->collection,
            'github_url' => $doc->githubUrl,
            'content' => $doc->content,
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputText(Documentation $doc): void
    {
        $this->components->info($this->getEntryTitlePrefix().": {$doc->title}");
        $this->newLine();

        $this->components->twoColumnDetail('<fg=cyan>ID</>', $doc->getId());
        $this->components->twoColumnDetail('<fg=cyan>Title</>', $doc->title);
        $this->components->twoColumnDetail('<fg=cyan>Collection</>', $doc->collection);
        $this->components->twoColumnDetail('<fg=cyan>GitHub URL</>', $doc->githubUrl);
        $this->newLine();

        if ($doc->content) {
            $this->line('<fg=yellow>Content:</>');
            $this->newLine();
            $this->line($doc->content);
        } else {
            $this->components->warn('No content available for this entry.');
        }
    }
}
