<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;

class StatamicPeakCommand extends Command
{
    protected $signature = 'statamic-context:peak';

    protected $description = 'Statamic Peak documentation commands';

    public function handle(): int
    {
        $this->components->info('Statamic Peak Documentation CLI');
        $this->newLine();

        $this->components->info('Available Commands:');
        $this->newLine();

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

        $this->line('  <fg=gray># Interactive search</>');
        $this->line('  php artisan statamic-context:peak:search --interactive');
        $this->newLine();

        $this->line('  <fg=gray># Search with query</>');
        $this->line('  php artisan statamic-context:peak:search');
        $this->newLine();

        $this->line('  <fg=gray># Get specific entry</>');
        $this->line('  php artisan statamic-context:peak:get features:page-builder');
        $this->newLine();

        $this->line('  <fg=gray># Update documentation</>');
        $this->line('  php artisan statamic-context:peak:update');

        return self::SUCCESS;
    }
}
