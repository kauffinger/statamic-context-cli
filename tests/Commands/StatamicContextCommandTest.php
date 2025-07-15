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
        ->expectsOutputToContain('php artisan docs:search --interactive')
        ->expectsOutputToContain('php artisan docs:get core:collections')
        ->expectsOutputToContain('php artisan docs:update')
        ->assertExitCode(0);
});