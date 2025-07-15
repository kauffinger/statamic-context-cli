<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext;

use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use StatamicContext\StatamicContext\Commands\StatamicContextCommand;
use StatamicContext\StatamicContext\Commands\StatamicContextGetCommand;
use StatamicContext\StatamicContext\Commands\StatamicContextSearchCommand;
use StatamicContext\StatamicContext\Commands\StatamicPeakCommand;
use StatamicContext\StatamicContext\Commands\StatamicPeakGetCommand;
use StatamicContext\StatamicContext\Commands\StatamicPeakSearchCommand;
use StatamicContext\StatamicContext\Commands\UpdateDocsCommand;
use StatamicContext\StatamicContext\Commands\UpdatePeakDocsCommand;
use StatamicContext\StatamicContext\Contracts\DocumentationRepository;
use StatamicContext\StatamicContext\Repositories\FileDocumentationRepository;
use StatamicContext\StatamicContext\Services\DocumentationFetcher;

class StatamicContextServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->bind(DocumentationRepository::class, fn ($app) => new FileDocumentationRepository(
            new Filesystem,
            config('statamic-context-cli.docs.index_file'),
        ));

        $this->app->bind(DocumentationFetcher::class, fn ($app) => new DocumentationFetcher(
            new Client([
                'base_uri' => 'https://api.github.com/',
                'timeout' => 30,
            ]),
            $app->make(DocumentationRepository::class),
            new Filesystem,
        ));
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('statamic-context-cli')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(StatamicContextCommand::class)
            ->hasCommand(StatamicContextSearchCommand::class)
            ->hasCommand(StatamicContextGetCommand::class)
            ->hasCommand(UpdateDocsCommand::class)
            ->hasCommand(StatamicPeakCommand::class)
            ->hasCommand(StatamicPeakSearchCommand::class)
            ->hasCommand(StatamicPeakGetCommand::class)
            ->hasCommand(UpdatePeakDocsCommand::class);
    }
}
