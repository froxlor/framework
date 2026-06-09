<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\EnvUsage;
use Froxlor\Core\Support\Resource;

class EnvUsageObserver
{
    /**
     * Handle the EnvUsage "created" event.
     */
    public function created(EnvUsage $envUsage): void
    {
        // add usage of environment also to the owning tenant
        Resource::addEnvironmentUsage($envUsage->environment, $envUsage->resource);
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
        // remove usage of environment also from the owning tenant
        Resource::removeEnvironmentUsage($envUsage->environment, $envUsage->resource);
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
