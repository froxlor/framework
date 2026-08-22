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

        $package = $this->argument('package');

        // A real terminal here means a hook that needs admin input (e.g. via Laravel Prompts)
        // can genuinely block and ask for it, instead of only ever falling back to the web UI.
        $interactive = $this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN);

        $service = $packageService->updatePackage($package, $interactive);

        if ($service['status'] == 'success') {
            $this->info($service['message']);
        } else {
            $this->error($service['message']);
        }
    }
}
