<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;

class StatamicContextCommand extends Command
{
    protected $signature = 'statamic-context';

    protected $description = 'Statamic documentation commands';

    public function handle(): int
    {
        $this->components->info('Statamic Documentation CLI');
        $this->newLine();

        $this->components->info('Available Commands:');
        $this->newLine();

        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:docs:search</>',
            'Search through Statamic documentation'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:docs:get {id}</>',
            'Retrieve a specific documentation entry by ID'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:docs:update</>',
            'Fetch and update documentation from GitHub'
        );

        $this->newLine();
        $this->components->info('Peak Documentation Commands:');
        $this->newLine();

        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:peak</>',
            'Statamic Peak documentation commands'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:peak:search</>',
            'Search through Statamic Peak documentation'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:peak:get {id}</>',
            'Retrieve a specific Peak documentation entry by ID'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>statamic-context:peak:update</>',
            'Fetch and update Peak documentation from GitHub'
        );

        $this->newLine();
        $this->components->info('Usage Examples:');
        $this->newLine();

        $this->line('  <fg=gray># Search Statamic docs</>');
        $this->line('  php artisan statamic-context:docs:search --interactive');
        $this->newLine();

        $this->line('  <fg=gray># Search Peak docs</>');
        $this->line('  php artisan statamic-context:peak:search page-builder');
        $this->newLine();

        $this->line('  <fg=gray># Get specific entry</>');
        $this->line('  php artisan statamic-context:docs:get core:collections');
        $this->line('  php artisan statamic-context:peak:get features:page-builder');
        $this->newLine();

        $this->line('  <fg=gray># Update documentation</>');
        $this->line('  php artisan statamic-context:docs:update');
        $this->line('  php artisan statamic-context:peak:update');

        return self::SUCCESS;
    }
}
