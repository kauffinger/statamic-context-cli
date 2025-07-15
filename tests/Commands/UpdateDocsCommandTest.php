<?php

declare(strict_types=1);

use StatamicContext\StatamicContext\Commands\UpdateDocsCommand;
use StatamicContext\StatamicContext\Services\DocumentationFetcher;

it('successfully updates documentation', function () {
    $stats = [
        'total' => 150,
        'updated' => 25,
        'errors' => 0,
    ];

    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')->once()->andReturn($stats);

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class)
        ->expectsOutputToContain('Fetching Statamic documentation from GitHub...')
        ->expectsOutputToContain('Documentation update completed')
        ->expectsOutputToContain('Total files processed')
        ->expectsOutputToContain('Files updated')
        ->assertExitCode(0);
});

it('shows errors in update statistics', function () {
    $stats = [
        'total' => 100,
        'updated' => 20,
        'errors' => 5,
    ];

    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')->once()->andReturn($stats);

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class)
        ->expectsOutputToContain('Documentation update completed')
        ->expectsOutputToContain('Total files processed')
        ->expectsOutputToContain('Files updated')
        ->expectsOutputToContain('Errors encountered')
        ->assertExitCode(0);
});

it('handles update failures gracefully', function () {
    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')
        ->once()
        ->andThrow(new Exception('GitHub API rate limit exceeded'));

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class)
        ->expectsOutputToContain('Fetching Statamic documentation from GitHub...')
        ->expectsOutputToContain('Failed to update documentation: GitHub API rate limit exceeded')
        ->assertExitCode(1);
});

it('shows exception trace in verbose mode', function () {
    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')
        ->once()
        ->andThrow(new Exception('Network error'));

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class, ['--verbose' => true])
        ->expectsOutputToContain('Failed to update documentation: Network error')
        ->assertExitCode(1);
});

it('supports force option', function () {
    $stats = [
        'total' => 200,
        'updated' => 200, // All files updated when forced
        'errors' => 0,
    ];

    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')->once()->andReturn($stats);

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class, ['--force' => true])
        ->expectsOutputToContain('Documentation update completed')
        ->expectsOutputToContain('200')
        ->assertExitCode(0);
});

it('displays completion time', function () {
    $stats = [
        'total' => 50,
        'updated' => 10,
        'errors' => 0,
    ];

    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')->once()->andReturn($stats);

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class)
        ->expectsOutputToContain('completed in')
        ->assertExitCode(0);
});

it('handles zero updates gracefully', function () {
    $stats = [
        'total' => 100,
        'updated' => 0,
        'errors' => 0,
    ];

    $fetcher = Mockery::mock(DocumentationFetcher::class);
    $fetcher->shouldReceive('fetchAll')->once()->andReturn($stats);

    $this->app->instance(DocumentationFetcher::class, $fetcher);

    $this->artisan(UpdateDocsCommand::class)
        ->expectsOutputToContain('Documentation update completed')
        ->expectsOutputToContain('Total files processed')
        ->expectsOutputToContain('Files updated')
        ->assertExitCode(0);
});