<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Repositories;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Models\Documentation;

class FileDocumentationRepository implements DocumentationRepository
{
    /**
     * @var Collection<int, Documentation>|null
     */
    private ?Collection $index = null;

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $indexPath,
    ) {}

    /**
     * @return Collection<int, Documentation>
     */
    public function all(): Collection
    {
        return $this->loadIndex();
    }

    public function find(string $collection, string $filename): ?Documentation
    {
        return $this->loadIndex()
            ->first(fn (Documentation $doc) => $doc->collection === $collection && $doc->filename === $filename
            );
    }

    public function findById(string $id): ?Documentation
    {
        // Parse ID in format "collection:filename"
        $parts = explode(':', $id, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$collection, $filename] = $parts;

        return $this->find($collection, $filename);
    }

    /**
     * @return Collection<int, Documentation>
     */
    public function search(string $query): Collection
    {
        $docs = $this->loadIndex();
        $queryLower = strtolower(trim($query));

        if ($queryLower === '' || $queryLower === '0') {
            return new Collection;
        }

        $titleWeight = config('statamic-context-cli.search.title_weight', 3.0);
        $contentWeight = config('statamic-context-cli.search.content_weight', 1.0);

        // Split query into words for multi-word search
        $queryWords = preg_split('/\s+/', $queryLower);
        $isMultiWord = count($queryWords) > 1;

        // Score each document
        $scored = $docs->map(function (Documentation $doc) use ($queryLower, $queryWords, $titleWeight, $contentWeight, $isMultiWord) {
            $score = 0;
            $titleLower = strtolower($doc->title);
            $contentLower = $doc->content ? strtolower($doc->content) : '';

            // For single word queries, use the original logic
            if (! $isMultiWord) {
                // Title matching (higher weight)
                if (str_contains($titleLower, $queryLower)) {
                    $score += $titleWeight;
                    // Exact title match gets extra points
                    if ($titleLower === $queryLower) {
                        $score += $titleWeight * 2;
                    }
                    // Title starts with query gets bonus
                    if (str_starts_with($titleLower, $queryLower)) {
                        $score += $titleWeight;
                    }
                }

                // Content matching
                if ($contentLower && str_contains($contentLower, $queryLower)) {
                    $score += $contentWeight;
                    // Multiple occurrences increase score
                    $occurrences = substr_count($contentLower, $queryLower);
                    $score += ($occurrences - 1) * ($contentWeight * 0.1);
                }
            } else {
                // Multi-word search: score based on individual words
                $titleWordsFound = 0;
                $contentWordsFound = 0;

                foreach ($queryWords as $word) {
                    // Title matching
                    if (str_contains($titleLower, $word)) {
                        $titleWordsFound++;
                        $score += $titleWeight * 0.5; // Lower weight per word
                    }

                    // Content matching
                    if ($contentLower && str_contains($contentLower, $word)) {
                        $contentWordsFound++;
                        $occurrences = substr_count($contentLower, $word);
                        $score += $contentWeight * 0.3 * $occurrences;
                    }
                }

                // Bonus for finding all words
                if ($titleWordsFound === count($queryWords)) {
                    $score += $titleWeight;
                }
                if ($contentWordsFound === count($queryWords)) {
                    $score += $contentWeight;
                }

                // Extra bonus if exact phrase is found
                if (str_contains($titleLower, $queryLower)) {
                    $score += $titleWeight * 1.5;
                }
                if ($contentLower && str_contains($contentLower, $queryLower)) {
                    $score += $contentWeight * 1.5;
                }
            }

            return ['doc' => $doc, 'score' => $score];
        })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(50)
            ->map(fn ($item) => $item['doc'])
            ->values();

        return $scored;
    }

    public function save(Documentation $documentation, string $content): void
    {
        // Ensure directory exists
        $directory = dirname($documentation->filePath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        // Save the content
        $this->files->put($documentation->filePath, $content);

        // Update index
        $index = $this->loadIndex();

        // Remove existing entry if present
        $index = $index->reject(fn (Documentation $doc) => $doc->collection === $documentation->collection &&
            $doc->filename === $documentation->filename
        );

        // Create documentation with content if indexing is enabled
        $indexContent = config('statamic-context-cli.search.index_content', true);
        $docToIndex = $indexContent ? new Documentation(
            collection: $documentation->collection,
            filename: $documentation->filename,
            title: $documentation->title,
            filePath: $documentation->filePath,
            githubUrl: $documentation->githubUrl,
            lastUpdated: $documentation->lastUpdated,
            content: $content
        ) : $documentation;

        // Add new entry
        $index->push($docToIndex);

        $this->saveIndex($index);
    }

    /**
     * @param  Collection<int, Documentation>  $items
     */
    public function saveMany(Collection $items): void
    {
        $indexContent = config('statamic-context-cli.search.index_content', true);

        if ($indexContent) {
            // Load content for all items before saving to index
            $itemsWithContent = $items->map(function (Documentation $doc) {
                if ($doc->content === null && $this->files->exists($doc->filePath)) {
                    $content = $this->files->get($doc->filePath);

                    return new Documentation(
                        collection: $doc->collection,
                        filename: $doc->filename,
                        title: $doc->title,
                        filePath: $doc->filePath,
                        githubUrl: $doc->githubUrl,
                        lastUpdated: $doc->lastUpdated,
                        content: $content
                    );
                }

                return $doc;
            });

            $this->saveIndex($itemsWithContent);
            $this->index = $itemsWithContent;
        } else {
            $this->saveIndex($items);
            $this->index = $items;
        }

        $this->clearInMemoryCache();
    }

    public function exists(): bool
    {
        return $this->files->exists($this->indexPath);
    }

    public function count(): int
    {
        return $this->loadIndex()->count();
    }

    /**
     * @return Collection<int, Documentation>
     */
    private function loadIndex(): Collection
    {
        if ($this->index instanceof Collection) {
            return $this->index;
        }

        if (! $this->files->exists($this->indexPath)) {
            $this->index = new Collection;

            return $this->index;
        }

        $data = json_decode($this->files->get($this->indexPath), true);

        $this->index = new Collection($data ?? []);
        $this->index = $this->index->map(fn (array $item) => Documentation::fromArray($item));

        return $this->index;
    }

    /**
     * @param  Collection<int, Documentation>  $index
     */
    private function saveIndex(Collection $index): void
    {
        $directory = dirname($this->indexPath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $data = $index->map(fn (Documentation $doc) => $doc->toArray())->values()->toArray();

        $this->files->put($this->indexPath, json_encode($data, JSON_PRETTY_PRINT));

        $this->index = $index;
    }

    private function clearInMemoryCache(): void
    {
        // Clear in-memory cache
        $this->index = null;
    }

    /**
     * Rebuild index with content for better search performance
     */
    public function rebuildIndexWithContent(): void
    {
        $index = $this->loadIndex();

        // Load content for all documents and save back to index
        $indexWithContent = $index->map(function (Documentation $doc) {
            if ($doc->content === null && $this->files->exists($doc->filePath)) {
                $content = $this->files->get($doc->filePath);

                return new Documentation(
                    collection: $doc->collection,
                    filename: $doc->filename,
                    title: $doc->title,
                    filePath: $doc->filePath,
                    githubUrl: $doc->githubUrl,
                    lastUpdated: $doc->lastUpdated,
                    content: $content
                );
            }

            return $doc;
        });

        $this->saveIndex($indexWithContent);
        $this->index = $indexWithContent;
        $this->clearInMemoryCache();
    }
}
