<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use StatamicContext\StatamicContext\Commands\StatamicContextSearchCommand;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Models\Documentation;

it('can search with query argument', function () {
    $doc = new Documentation(
        collection: 'docs',
        filename: 'collections.md',
        title: 'Collections Guide',
        filePath: '/path/to/collections.md',
        githubUrl: 'https://github.com/statamic/docs/blob/master/content/collections/docs/collections.md',
        lastUpdated: Carbon::now(),
        content: 'This is a guide about collections in Statamic.'
    );

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('search')->with('collections')->once()->andReturn(collect([$doc]));

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class, ['query' => 'collections'])
        ->expectsOutputToContain('Found 1 results')
        ->expectsOutputToContain('Collections Guide')
        ->assertExitCode(0);
});

it('shows help when no query provided', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('count')->once()->andReturn(100);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class)
        ->expectsQuestion('Enter your search query (or press Enter to exit):', '')
        ->expectsOutputToContain('Statamic Context CLI - Search through Statamic documentation')
        ->expectsOutputToContain('Usage')
        ->expectsOutputToContain('Examples:')
        ->expectsOutputToContain('statamic-context:docs:search collections')
        ->assertExitCode(0);
});

it('handles empty search results', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('search')->with('nonexistent')->once()->andReturn(collect([]));

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class, ['query' => 'nonexistent'])
        ->expectsOutputToContain('No results found.')
        ->expectsOutputToContain('Try different search terms')
        ->assertExitCode(0);
});

it('returns error when repository does not exist', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(false);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class, ['query' => 'test'])
        ->assertExitCode(1);
});

it('displays multiple search results with limit', function () {
    $docs = collect([
        new Documentation(
            collection: 'docs',
            filename: 'collections.md',
            title: 'Collections Guide',
            filePath: '/path/to/collections.md',
            githubUrl: 'https://github.com/example/collections.md',
            lastUpdated: Carbon::now(),
            content: 'Guide about collections'
        ),
        new Documentation(
            collection: 'docs',
            filename: 'blueprints.md',
            title: 'Blueprints Guide',
            filePath: '/path/to/blueprints.md',
            githubUrl: 'https://github.com/example/blueprints.md',
            lastUpdated: Carbon::now(),
            content: 'Guide about blueprints'
        ),
    ]);

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('search')->with('guide')->once()->andReturn($docs);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class, ['query' => 'guide'])
        ->expectsOutputToContain('Found 2 results')
        ->expectsOutputToContain('Collections Guide')
        ->expectsOutputToContain('Blueprints Guide')
        ->assertExitCode(0);
});

it('respects limit option', function () {
    $docs = collect([
        new Documentation(
            collection: 'docs',
            filename: 'doc1.md',
            title: 'Document 1',
            filePath: '/path/to/doc1.md',
            githubUrl: 'https://github.com/example/doc1.md',
            lastUpdated: Carbon::now(),
            content: 'First document'
        ),
        new Documentation(
            collection: 'docs',
            filename: 'doc2.md',
            title: 'Document 2',
            filePath: '/path/to/doc2.md',
            githubUrl: 'https://github.com/example/doc2.md',
            lastUpdated: Carbon::now(),
            content: 'Second document'
        ),
        new Documentation(
            collection: 'docs',
            filename: 'doc3.md',
            title: 'Document 3',
            filePath: '/path/to/doc3.md',
            githubUrl: 'https://github.com/example/doc3.md',
            lastUpdated: Carbon::now(),
            content: 'Third document'
        ),
    ]);

    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('search')->with('document')->once()->andReturn($docs);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class, ['query' => 'document', '--limit' => '2'])
        ->expectsOutputToContain('Found 3 results (showing 2)')
        ->expectsOutputToContain('Document 1')
        ->expectsOutputToContain('Document 2')
        ->expectsOutputToContain('Use --limit option to see more results')
        ->assertExitCode(0);
});

it('shows documentation status when repository exists', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(true);
    $repository->shouldReceive('count')->once()->andReturn(150);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class)
        ->expectsQuestion('Enter your search query (or press Enter to exit):', '')
        ->expectsOutputToContain('150 documents available')
        ->assertExitCode(0);
});

it('shows warning when repository does not exist during help', function () {
    $repository = Mockery::mock(DocumentationRepository::class);
    $repository->shouldReceive('exists')->once()->andReturn(false);

    $this->app->instance(DocumentationRepository::class, $repository);

    $this->artisan(StatamicContextSearchCommand::class)
        ->expectsQuestion('Enter your search query (or press Enter to exit):', '')
        ->expectsOutputToContain('No documentation found. Run statamic-context:docs:update first.')
        ->assertExitCode(0);
});
