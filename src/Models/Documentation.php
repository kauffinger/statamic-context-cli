<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @implements Arrayable<string, mixed>
 */
class Documentation implements Arrayable, Jsonable
{
    public function __construct(
        public readonly string $collection,
        public readonly string $filename,
        public readonly string $title,
        public readonly string $filePath,
        public readonly string $githubUrl,
        public readonly Carbon $lastUpdated,
        public ?string $content = null,
    ) {}

    public function getId(): string
    {
        return "{$this->collection}:{$this->filename}";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            collection: $data['collection'],
            filename: $data['filename'],
            title: $data['title'],
            filePath: $data['file_path'],
            githubUrl: $data['github_url'],
            lastUpdated: Carbon::parse($data['last_updated']),
            content: $data['content'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'collection' => $this->collection,
            'filename' => $this->filename,
            'title' => $this->title,
            'file_path' => $this->filePath,
            'github_url' => $this->githubUrl,
            'last_updated' => $this->lastUpdated->toIso8601String(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function getSlug(): string
    {
        return Str::before($this->filename, '.md');
    }

    public function matches(string $query): bool
    {
        $queryLower = strtolower($query);

        return str_contains(strtolower($this->title), $queryLower);
    }

    public function contentMatches(string $query): bool
    {
        if ($this->content === null) {
            return false;
        }

        return str_contains(strtolower($this->content), strtolower($query));
    }

    public function getSearchableContent(): string
    {
        return $this->content ?? '';
    }
}
