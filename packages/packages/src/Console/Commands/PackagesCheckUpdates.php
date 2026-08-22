<?php

namespace Froxlor\Packages\Console\Commands;

use Froxlor\Core\Models\Tenant;
use Froxlor\Packages\Notifications\PackageUpdatesAvailable;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Console\Command;

class PackagesCheckUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:packages:check-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for available composer package updates and notify the root tenant';

    /**
     * Execute the console command.
     */
    public function handle(PackageService $packageService)
    {
        $this->line('Checking for available package updates...');

        $updates = $packageService->availableUpdates();
        $rootTenants = Tenant::query()->root()->get();

        if ($updates === []) {
            // Updates announced earlier but not acted upon are obsolete once
            // everything is up to date again (e.g. updated via CLI).
            foreach ($rootTenants as $tenant) {
                $tenant->unreadNotifications()
                    ->where('type', PackageUpdatesAvailable::class)
                    ->delete();
            }

            $this->info('All packages are up to date.');
            return;
        }

        $packages = PackageUpdatesAvailable::describeUpdates($updates);

        foreach ($rootTenants as $tenant) {
            // Don't renotify (or unread a dismissed notification) as long as the
            // available updates haven't changed since the last announcement.
            $latest = $tenant->notifications()
                ->where('type', PackageUpdatesAvailable::class)
                ->first();

            if ($latest !== null && ($latest->data['packages'] ?? null) === $packages) {
                continue;
            }

            $tenant->unreadNotifications()
                ->where('type', PackageUpdatesAvailable::class)
                ->delete();

            $tenant->notify(new PackageUpdatesAvailable($updates));
        }

        $this->info(sprintf(
            '%d package update(s) available: %s',
            count($packages),
            implode(', ', $packages)
        ));
    }
}
