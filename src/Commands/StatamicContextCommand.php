<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

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
            '<fg=cyan>docs:search</>',
            'Search through Statamic documentation'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>docs:get {id}</>',
            'Retrieve a specific documentation entry by ID'
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>docs:update</>',
            'Fetch and update documentation from GitHub'
        );

        $this->newLine();
        $this->components->info('Usage Examples:');
        $this->newLine();

        $this->line('  <fg=gray># Interactive search</>');
        $this->line('  php artisan docs:search --interactive');
        $this->newLine();

        $this->line('  <fg=gray># Search with query</>');
        $this->line('  php artisan docs:search');
        $this->newLine();

        $this->line('  <fg=gray># Get specific entry</>');
        $this->line('  php artisan docs:get core:collections');
        $this->newLine();

        $this->line('  <fg=gray># Update documentation</>');
        $this->line('  php artisan docs:update');

        return self::SUCCESS;
    }
}
