<?php

namespace Froxlor\Packages\Console\Commands;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class PackagesVerify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:packages:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that the application still boots successfully with the currently installed packages';

    /**
     * Execute the console command.
     */
    public function handle(PackageService $packageService)
    {
        $result = $packageService->verifyBoot();

        if ($result['status'] === 'ok') {
            $this->info('The application boots successfully.');

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
