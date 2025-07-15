<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Exceptions\DocumentationException;
use StatamicContext\StatamicContext\Models\Documentation;
use Symfony\Component\Console\Attribute\AsCommand;

class StatamicContextGetCommand extends Command
{
    protected $signature = 'statamic-context:docs:get
                            {id : The ID of the documentation entry to retrieve}
                            {--format=text : Output format (text, json)}';

    protected $description = 'Retrieve a specific documentation entry by ID';

    public function handle(DocumentationRepository $repository): int
    {
        try {
            $id = $this->argument('id');
            $format = $this->option('format');

            if (! $repository->exists()) {
                $this->components->error('No documentation found. Run update-docs first.');

                return self::FAILURE;
            }

            $doc = $repository->findById($id);

            if (! $doc instanceof Documentation) {
                $this->components->error("Documentation entry with ID '{$id}' not found.");

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
        $this->components->info("Documentation Entry: {$doc->title}");
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
