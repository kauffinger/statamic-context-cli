<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Models\Documentation;

use function Laravel\Prompts\progress;

class DocumentationFetcher
{
    public function __construct(
        private readonly Client $client,
        private readonly DocumentationRepository $repository,
        private readonly Filesystem $files,
    ) {}

    /**
     * Fetch all documentation from GitHub.
     *
     * @return array{total: int, updated: int, errors: int}
     */
    public function fetchAll(string $configKey = 'docs', ?Command $command = null): array
    {
        $stats = ['total' => 0, 'updated' => 0, 'errors' => 0];
        $allDocumentation = collect();

        $collections = config("statamic-context-cli.{$configKey}.collections", []);

        if ($command instanceof Command) {
            $command->getOutput()->writeln("<info>📂 Scanning {$configKey} collections...</info>");
        }

        foreach ($collections as $collection) {
            $result = $this->fetchCollection($collection, $allDocumentation, $configKey, $command);
            $stats['total'] += $result['total'];
            $stats['updated'] += $result['updated'];
            $stats['errors'] += $result['errors'];
        }

        // Save all documentation at once with progress
        if ($command && $allDocumentation->isNotEmpty()) {
            $command->getOutput()->writeln("<info>💾 Building search index with {$allDocumentation->count()} documents...</info>");
            $this->repository->saveMany($allDocumentation);
        } else {
            $this->repository->saveMany($allDocumentation);
        }

        return $stats;
    }

    /**
     * Fetch a single collection from GitHub.
     *
     * @param  Collection<int, Documentation>  $allDocumentation
     * @return array{total: int, updated: int, errors: int}
     */
    private function fetchCollection(string $collection, Collection $allDocumentation, string $configKey = 'docs', ?Command $command = null): array
    {
        $stats = ['total' => 0, 'updated' => 0, 'errors' => 0];

        try {
            if ($command instanceof Command) {
                $command->getOutput()->writeln("<info>📡 Fetching file list for {$collection}...</info>");
            }

            $files = $this->getCollectionFiles($collection, $configKey);

            $stats['total'] = $files->count();
            $markdownFiles = $files->filter(fn (array $file) => $file['type'] === 'file' && Str::endsWith($file['name'], '.md'));

            if ($markdownFiles->isEmpty()) {
                return $stats;
            }

            if ($command instanceof Command) {
                // Use progress bar for file downloads
                $progressBar = progress(
                    label: "📥 Downloading {$collection} files",
                    steps: $markdownFiles->count()
                );

                $progressBar->start();

                $markdownFiles->each(function (array $file) use ($collection, &$stats, $allDocumentation, $configKey, $progressBar) {
                    try {
                        $documentation = $this->fetchAndProcessFile($collection, $file, $configKey);
                        $allDocumentation->push($documentation);
                        $stats['updated']++;
                        $progressBar->advance();
                    } catch (Exception) {
                        $stats['errors']++;
                        $progressBar->advance();
                    }
                });

                $progressBar->finish();
            } else {
                $markdownFiles->each(function (array $file) use ($collection, &$stats, $allDocumentation, $configKey) {
                    try {
                        $documentation = $this->fetchAndProcessFile($collection, $file, $configKey);
                        $allDocumentation->push($documentation);
                        $stats['updated']++;
                    } catch (Exception) {
                        $stats['errors']++;
                    }
                });
            }
        } catch (Exception) {
            $stats['errors']++;
        }

        return $stats;
    }

    /**
     * Get files from a GitHub collection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getCollectionFiles(string $collection, string $configKey = 'docs'): Collection
    {
        $repo = config("statamic-context-cli.{$configKey}.github_repo");
        $branch = config("statamic-context-cli.{$configKey}.github_branch");

        // Different path structure for Peak docs vs Statamic docs
        $path = $configKey === 'peak_docs'
            ? $collection
            : "content/collections/{$collection}";

        $response = $this->client->get("repos/{$repo}/contents/{$path}", [
            'query' => ['ref' => $branch],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return new Collection($data ?? []);
    }

    /**
     * Fetch and process a single file.
     *
     * @param  array<string, mixed>  $file
     */
    private function fetchAndProcessFile(string $collection, array $file, string $configKey = 'docs'): Documentation
    {
        $response = $this->client->get($file['download_url']);
        $content = $response->getBody()->getContents();

        $parsed = $this->parseContent($content);

        $storagePath = config("statamic-context-cli.{$configKey}.storage_path");
        $localPath = "{$storagePath}/{$collection}/{$file['name']}";

        // Ensure directory exists
        $directory = dirname($localPath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        // Save the raw content
        $this->files->put($localPath, $content);

        return new Documentation(
            collection: $collection,
            filename: $file['name'],
            title: $parsed['title'] ?? Str::headline(Str::before($file['name'], '.md')),
            filePath: $localPath,
            githubUrl: $file['html_url'],
            lastUpdated: now(),
        );
    }

    /**
     * Parse frontmatter and content from markdown.
     *
     * @return array{title: ?string, content: string, frontmatter: array<string, string>}
     */
    private function parseContent(string $content): array
    {
        $lines = collect(explode("\n", $content));
        $frontmatter = collect();
        $contentLines = collect();
        $state = 'start';

        $lines->each(function (string $line) use (&$state, $frontmatter, $contentLines) {
            if ($line === '---' && $state === 'start') {
                $state = 'frontmatter';

                return;
            }

            if ($line === '---' && $state === 'frontmatter') {
                $state = 'content';

                return;
            }

            if ($state === 'frontmatter' && Str::contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $frontmatter->put(trim($key), trim($value));
            } elseif ($state === 'content' || ($state === 'start' && $line !== '---')) {
                $contentLines->push($line);
                if ($state === 'start') {
                    $state = 'content';
                }
            }
        });

        return [
            'frontmatter' => $frontmatter->toArray(),
            'content' => $contentLines->implode("\n"),
            'title' => $frontmatter->get('title'),
        ];
    }
}
