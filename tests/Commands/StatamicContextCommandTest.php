<?php

declare(strict_types=1);

use StatamicContext\StatamicContext\Commands\StatamicContextCommand;

it('displays help information', function () {
    $this->artisan(StatamicContextCommand::class)
        ->expectsOutputToContain('Statamic Documentation CLI')
        ->assertExitCode(0);
});

it('shows available commands', function () {
    $this->artisan(StatamicContextCommand::class)
        ->expectsOutputToContain('docs:search')
        ->expectsOutputToContain('docs:get')
        ->expectsOutputToContain('docs:update')
        ->assertExitCode(0);
});

it('shows usage examples', function () {
    $this->artisan(StatamicContextCommand::class)
        ->expectsOutputToContain('Usage Examples:')
        ->expectsOutputToContain('php artisan statamic-context:docs:search --interactive')
        ->expectsOutputToContain('php artisan statamic-context:docs:get core:collections')
        ->expectsOutputToContain('php artisan statamic-context:docs:update')
        ->assertExitCode(0);
});
