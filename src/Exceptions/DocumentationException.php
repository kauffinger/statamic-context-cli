<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Exceptions;

use Exception;

class DocumentationException extends Exception
{
    public static function indexNotFound(): self
    {
        return new self('Documentation index not found. Please run statamic-context-cli:update-docs first.');
    }

    public static function fetchFailed(string $collection, string $reason): self
    {
        return new self("Failed to fetch documentation for collection '{$collection}': {$reason}");
    }

    public static function githubApiError(string $message): self
    {
        return new self("GitHub API error: {$message}");
    }

    public static function storageFailed(string $path): self
    {
        return new self("Failed to store documentation at path: {$path}");
    }
}
