<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use StatamicContext\StatamicContext\Commands\StatamicContextGetCommand;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Models\Documentation;

it('can retrieve documentation by ID', function () {
    $doc = new Documentation(
        collection: 'test',
        filename: 'example.md',
        title: 'Example Document',
        filePath: '/path/to/file.md',
        githubUrl: 'https://github.com/example',
        lastUpdated: Carbon::now(),
        content: 'Test content'
    );

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('test:example.md')->once()->andReturn($doc);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'test:example.md'])
        ->assertExitCode(0);
});

it('returns error for non-existent ID', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('invalid:id')->once()->andReturn(null);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'invalid:id'])
        ->assertExitCode(1);
});

it('returns error when repository does not exist', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(false);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'test:example.md'])
        ->expectsOutputToContain('No documentation found. Run update-docs first.')
        ->assertExitCode(1);
});

it('outputs documentation in text format by default', function () {
    $doc = new Documentation(
        collection: 'docs',
        filename: 'collections.md',
        title: 'Collections Guide',
        filePath: '/path/to/collections.md',
        githubUrl: 'https://github.com/statamic/docs/blob/master/content/collections/docs/collections.md',
        lastUpdated: Carbon::now(),
        content: 'This is a comprehensive guide about collections in Statamic.'
    );

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('docs:collections.md')->once()->andReturn($doc);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'docs:collections.md'])
        ->expectsOutputToContain('Documentation Entry: Collections Guide')
        ->expectsOutputToContain('Collections Guide')
        ->expectsOutputToContain('docs')
        ->expectsOutputToContain('Content:')
        ->expectsOutputToContain('This is a comprehensive guide about collections in Statamic.')
        ->assertExitCode(0);
});

it('supports JSON format option', function () {
    $doc = new Documentation(
        collection: 'docs',
        filename: 'blueprints.md',
        title: 'Blueprints Guide',
        filePath: '/path/to/blueprints.md',
        githubUrl: 'https://github.com/statamic/docs/blob/master/content/collections/docs/blueprints.md',
        lastUpdated: Carbon::now(),
        content: 'This is a guide about blueprints.'
    );

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('docs:blueprints.md')->once()->andReturn($doc);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, [
        'id' => 'docs:blueprints.md',
        '--format' => 'json',
    ])
        ->assertExitCode(0);
});

it('handles documentation with no content', function () {
    $doc = new Documentation(
        collection: 'docs',
        filename: 'empty.md',
        title: 'Empty Document',
        filePath: '/path/to/empty.md',
        githubUrl: 'https://github.com/example/empty.md',
        lastUpdated: Carbon::now(),
        content: null
    );

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('docs:empty.md')->once()->andReturn($doc);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'docs:empty.md'])
        ->expectsOutputToContain('Documentation Entry: Empty Document')
        ->expectsOutputToContain('No content available for this entry.')
        ->assertExitCode(0);
});

it('returns specific error message for non-existent ID', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('findById')->with('invalid:nonexistent')->once()->andReturn(null);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextGetCommand::class, ['id' => 'invalid:nonexistent'])
        ->expectsOutputToContain("Documentation entry with ID 'invalid:nonexistent' not found.")
        ->assertExitCode(1);
});
