<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\TenantUser;
use Froxlor\Core\Support\Resource;

class TenantUserObserver
{
    /**
     * Handle the TenantUser "created" event.
     */
    public function created(TenantUser $tenantUser): void
    {
        // add usage to the owning tenant
        Resource::addUsage($tenantUser->tenant, $tenantUser->user, auth()->user() ?? $tenantUser->user);
    }

    /**
     * Handle the TenantUser "updated" event.
     */
    public function updated(TenantUser $tenantUser): void
    {
        if ($tenantUser->wasChanged('plan_id')) {
            \Froxlor\Core\Support\PlanAssignments::ensureAssignableToTenantUser(
                $tenantUser->plan_id, $tenantUser->tenant()->lockForUpdate()->firstOrFail(), 'plan_id', $tenantUser->user_id);
        }
    }

    /**
     * Handle the TenantUser "deleted" event.
     */
    public function deleted(TenantUser $tenantUser): void
    {
        // Release the removed tenant membership's slot.
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
