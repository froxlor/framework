<?php

namespace Froxlor\Packages\Console\Commands;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class PackagesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:packages:sync {--installed=*} {--updated=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pending package migrations and fire install/update lifecycle hooks';

    /**
     * Execute the console command.
     *
     * Meant to run as a fresh subprocess right after a composer require/update: a package that
     * was just installed or upgraded isn't autoloadable in the process that shelled out to
     * composer, but it is here.
     *
     * installing()/updating() are called *before* `artisan migrate`, so a package can use
     * requireCompletion() to demand a setting its migrations depend on before that schema/data
     * change is ever applied. If any package in this run does so, migrations are held back
     * entirely for the whole run (not just for that package) — completeCompletion() runs them
     * once the admin has supplied what was missing, see PackageServiceProvider.
     */
    public function handle(PackageService $packageService)
    {
        $installed = $this->option('installed');
        $updated = $this->option('updated');
        $blocked = [];

        foreach ($installed as $package) {
            $provider = $packageService->findProvider($package);

            if (!$provider) {
                continue;
            }

            $provider->installing();

            if ($provider->hasPendingCompletion()) {
                $blocked[] = $package;
                $this->warn("Migrations are on hold: {$package} needs admin input before it can be installed.");
            }
        }

        foreach ($updated as $package) {
            $provider = $packageService->findProvider($package);

            if (!$provider) {
                continue;
            }

            $provider->updating();

            if ($provider->hasPendingCompletion()) {
                $blocked[] = $package;
                $this->warn("Migrations are on hold: {$package} needs admin input before it can be updated.");
            }
        }

        if ($blocked === []) {
            $this->call('migrate', ['--force' => true]);
        } else {
            $this->warn('Skipping migrations for this run. Resolve the pending package(s) above and they will run automatically.');
        }

        foreach ($installed as $package) {
            if (in_array($package, $blocked, true)) {
                continue;
            }

            $packageService->findProvider($package)?->installed();
        }

        foreach ($updated as $package) {
            if (in_array($package, $blocked, true)) {
                continue;
            }

            $packageService->findProvider($package)?->updated();
        }

        return self::SUCCESS;
    }
}
