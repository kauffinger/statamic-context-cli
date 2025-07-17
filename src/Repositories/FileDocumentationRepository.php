<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Repositories;

use Fuse\Fuse;
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

        $doc = $this->find($collection, $filename);

        // Load content if found
        if ($doc && $this->files->exists($doc->filePath)) {
            $content = $this->files->get($doc->filePath);
            $doc->content = $content;
        }

        return $doc;
    }

    /**
     * @return Collection<int, Documentation>
     */
    public function search(string $query): Collection
    {
        $fuzzyEnabled = config('statamic-context-cli.search.fuzzy_enabled', true);
        $threshold = config('statamic-context-cli.search.fuzzy_threshold', 0.3);

        return $this->searchWithFuzzy($query, $fuzzyEnabled, $threshold);
    }

    /**
     * @return Collection<int, Documentation>
     */
    public function searchWithFuzzy(string $query, bool $useFuzzy = true, float $threshold = 0.3): Collection
    {
        $allDocs = $this->loadIndex();

        // Load content for all documents
        $docsWithContent = $allDocs->map(function (Documentation $doc) {
            if ($this->files->exists($doc->filePath)) {
                $content = $this->files->get($doc->filePath);
                $doc->content = $content;
            }

            return $doc;
        });

        if ($useFuzzy) {
            return $this->fuzzySearch($docsWithContent, $query, $threshold);
        }

        // Fallback to exact search
        return $this->exactSearch($docsWithContent, $query);
    }

    /**
     * @param  Collection<int, Documentation>  $docs
     * @return Collection<int, Documentation>
     */
    private function fuzzySearch(Collection $docs, string $query, float $threshold): Collection
    {
        $titleWeight = config('statamic-context-cli.search.title_weight', 0.7);
        $contentWeight = config('statamic-context-cli.search.content_weight', 0.3);

        // Convert documents to array format for Fuse
        $fuseData = $docs->map(function (Documentation $doc) {
            return [
                'id' => $doc->getId(),
                'title' => $doc->title,
                'content' => $doc->getSearchableContent(),
                'doc' => $doc,
            ];
        })->toArray();

        // Configure Fuse options
        $options = [
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => $titleWeight,
                ],
                [
                    'name' => 'content',
                    'weight' => $contentWeight,
                ],
            ],
            'threshold' => 1.0 - $threshold, // Fuse uses distance, we use similarity
            'includeScore' => true,
            'ignoreLocation' => true,
            'findAllMatches' => true,
        ];

        $fuse = new Fuse($fuseData, $options);
        $results = $fuse->search($query);

        return collect($results)
            ->map(fn ($result) => $result['item']['doc'])
            ->take(50); // Limit results to avoid too many matches
    }

    /**
     * @param  Collection<int, Documentation>  $docs
     * @return Collection<int, Documentation>
     */
    private function exactSearch(Collection $docs, string $query): Collection
    {
        $results = collect();

        // Search in titles first
        $titleMatches = $docs->filter(fn (Documentation $doc) => $doc->matches($query));
        $results = $results->merge($titleMatches);

        // Then search in content
        $contentMatches = $docs
            ->reject(fn (Documentation $doc) => $titleMatches->contains($doc))
            ->filter(fn (Documentation $doc) => $doc->contentMatches($query));

        $results = $results->merge($contentMatches);

        return $results->sortBy('title')->values();
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

        // Add new entry
        $index->push($documentation);

        $this->saveIndex($index);
    }

    /**
     * @param  Collection<int, Documentation>  $items
     */
    public function saveMany(Collection $items): void
    {
        $this->saveIndex($items);
        $this->index = $items;
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
}
