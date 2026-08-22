<?php

namespace Froxlor\Packages\Console\Commands;

use Exception;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class RepositoriesInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:repositories:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize local repositories';

    /**
     * Execute the console command.
     */
    public function handle(PackageService $packageService)
    {
        $directory = config('packages.directory');

        if (is_dir($directory) && is_readable($directory)) {
            try {
                $this->line("Enable local packages folder $directory");
                $packageService->addLocalRepository('local', $directory . '/*');

                $this->line("Enable local packages from environment variable");
                $packageService->loadPackageRepository();
            } catch (Exception $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info("The folder $directory does not exist or is not readable.");
        }

        if ($token = config('packages.token')) {
            try {
                $this->line('Configure authentication for the froxlor package repository');
                $packageService->setRepositoryAuth('packages.froxlor.org', 'http-basic', [
                    'username' => 'developers',
                    'password' => $token,
                ]);
            } catch (Exception $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }
        }

        $this->line("Packages enabled.");

        return self::SUCCESS;
    }
}
