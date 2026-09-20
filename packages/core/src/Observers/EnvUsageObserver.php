<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\EnvUsage;

class EnvUsageObserver
{
    /**
     * Handle the EnvUsage "created" event.
     */
    public function created(EnvUsage $envUsage): void
    {
        // Tenant totals aggregate env_usage through environments. Never book a second row.
    }

    /**
     * Handle the EnvUsage "updated" event.
     */
    public function updated(EnvUsage $envUsage): void
    {
        //
    }

    /**
     * Handle the EnvUsage "deleted" event.
     */
    public function deleted(EnvUsage $envUsage): void
    {
        // Removing this ledger row already removes it from the owner's aggregate.
    }

    /**
     * Handle the EnvUsage "restored" event.
     */
    public function restored(EnvUsage $envUsage): void
    {
        //
    }

    /**
     * Handle the EnvUsage "force deleted" event.
     */
    public function forceDeleted(EnvUsage $envUsage): void
    {
        //
    }
}
