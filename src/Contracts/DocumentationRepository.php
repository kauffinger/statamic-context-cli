<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Contracts;

use Illuminate\Support\Collection;
use StatamicContext\StatamicContext\Models\Documentation;

interface DocumentationRepository
{
    /**
     * Get all documentation items.
     *
     * @return Collection<int, Documentation>
     */
    public function all(): Collection;

    /**
     * Find documentation by collection and filename.
     */
    public function find(string $collection, string $filename): ?Documentation;

    /**
     * Find documentation by ID (collection:filename format).
     */
    public function findById(string $id): ?Documentation;

    /**
     * Search documentation by query.
     *
     * @return Collection<int, Documentation>
     */
    public function search(string $query): Collection;

    /**
     * Save a documentation item.
     */
    public function save(Documentation $documentation, string $content): void;

    /**
     * Save multiple documentation items.
     *
     * @param  Collection<int, Documentation>  $items
     */
    public function saveMany(Collection $items): void;

    /**
     * Check if the repository has been initialized.
     */
    public function exists(): bool;

    /**
     * Get the count of documentation items.
     */
    public function count(): int;
}
