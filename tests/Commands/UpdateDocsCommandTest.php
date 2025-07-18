<?php

declare(strict_types=1);

use StatamicContext\StatamicContext\Commands\UpdateDocsCommand;
use StatamicContext\StatamicContext\Commands\UpdatePeakDocsCommand;

it('has correct command signature for Statamic docs', function () {
    $command = new UpdateDocsCommand;
    expect($command->getName())->toBe('statamic-context:docs:update');
    expect($command->getDescription())->toBe('Fetch and update Statamic documentation from GitHub');
});

it('has correct command signature for Peak docs', function () {
    $command = new UpdatePeakDocsCommand;
    expect($command->getName())->toBe('statamic-context:peak:update');
    expect($command->getDescription())->toBe('Fetch and update Statamic Peak documentation from GitHub');
});

it('commands are registered in service provider', function () {
    expect($this->app->make(UpdateDocsCommand::class))->toBeInstanceOf(UpdateDocsCommand::class);
    expect($this->app->make(UpdatePeakDocsCommand::class))->toBeInstanceOf(UpdatePeakDocsCommand::class);
});
