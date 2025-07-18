<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Services;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use StatamicContext\StatamicContext\Repositories\FileDocumentationRepository;

class PeakDocumentationService
{
    private readonly DocumentationFetcher $fetcher;

    private readonly FileDocumentationRepository $repository;

    public function __construct()
    {
        $this->repository = new FileDocumentationRepository(
            new Filesystem,
            config('statamic-context-cli.peak_docs.index_file'),
        );

        $this->fetcher = new DocumentationFetcher(
            new Client([
                'base_uri' => 'https://api.github.com/',
                'timeout' => 30,
            ]),
            $this->repository,
            new Filesystem,
        );
    }

    /**
     * Fetch all Peak documentation from GitHub.
     *
     * @return array{total: int, updated: int, errors: int}
     */
    public function fetchAll(?Command $command = null): array
    {
        return $this->fetcher->fetchAll('peak_docs', $command);
    }

    public function getRepository(): FileDocumentationRepository
    {
        return $this->repository;
    }
}
