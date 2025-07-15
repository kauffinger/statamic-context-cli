<?php

namespace StatamicContext\StatamicContext;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use StatamicContext\StatamicContext\Commands\StatamicContextCommand;

class StatamicContextServiceProvider extends PackageServiceProvider
{
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
            ->hasMigration('create_statamic_context_cli_table')
            ->hasCommand(StatamicContextCommand::class);
    }
}
