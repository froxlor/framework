<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\TenantUser;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Resource;

class TenantUserObserver
{
    /**
     * Handle the TenantUser "created" event.
     */
    public function created(TenantUser $tenantUser): void
    {
        // add usage to the owning tenant
        Resource::addUsage($tenantUser->tenant, $tenantUser->user, $tenantUser->user);
    }

    /**
     * Handle the TenantUser "updated" event.
     */
    public function updated(TenantUser $tenantUser): void
    {
        //
    }

    /**
     * Handle the TenantUser "deleted" event.
     */
    public function deleted(TenantUser $tenantUser): void
    {
        // remove usage of environment also from the owning tenant
        Resource::removeUsage($tenantUser->tenant, $tenantUser->user);
    }

    /**
     * Handle the TenantUser "restored" event.
     */
    public function restored(TenantUser $tenantUser): void
    {
        //
    }

    /**
     * Handle the TenantUser "force deleted" event.
     */
    public function forceDeleted(TenantUser $tenantUser): void
    {
        //
    }
}
