<?php

namespace Froxlor\Packages\Console\Commands;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class PackagesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:packages:update {package?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update packages';

    /**
     * Execute the console command.
     */
    public function handle(PackageService $packageService)
    {
        $this->line('Start updating packages...');

        $packageService->updateRepositories();
        $package = $this->argument('package');
        $service = $packageService->updatePackage($package);

        if ($service['status'] == 'success') {
            $this->info($service['message']);
        } else {
            $this->error($service['message']);
        }
    }
}
