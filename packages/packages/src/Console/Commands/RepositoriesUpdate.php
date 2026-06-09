<?php

namespace Froxlor\Packages\Console\Commands;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class RepositoriesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:repositories:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update repositories';

    /**
     * Execute the console command.
     */
    public function handle(PackageService $packageService)
    {
        $this->line('Start updating repositories...');

        $packageService->updateRepositories();

        $this->info('Repositories updated successfully.');
    }
}
