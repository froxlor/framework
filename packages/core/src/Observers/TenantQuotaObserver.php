<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\TenantResourceReservation;
use Froxlor\Core\Models\TenantUsage;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Resource;

class TenantQuotaObserver
{
    public function created(Tenant $tenant): void
    {
        $parent = $tenant->parentTenant()->lockForUpdate()->first();
        if ($parent === null) {
            return;
        }
        if (auth()->check()) {
            Resource::addUsage($parent, $tenant, auth()->user());
        }
        $plan = $tenant->plan()->lockForUpdate()->firstOrFail();
        PlanAssignments::ensureAssignableToChildTenant($plan, $parent, $tenant);
        PlanAssignments::syncTenantReservations($parent, $tenant, $plan);
    }

    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged(['plan_id', 'parent_tenant_id'])) {
            $parent = $tenant->parentTenant()->lockForUpdate()->first();
            $plan = $tenant->plan()->lockForUpdate()->firstOrFail();
            $oldParent = $tenant->getRawOriginal('parent_tenant_id');
            if ($oldParent !== null && $oldParent !== $tenant->parent_tenant_id) {
                TenantResourceReservation::query()->where('tenant_id', $oldParent)
                    ->where('reserved_for_tenant_id', $tenant->id)->delete();
                TenantUsage::query()->where('tenant_id', $oldParent)
                    ->where('resource_key', Tenant::getResourceKey())->where('resource_id', $tenant->id)->delete();
            }
            if ($oldParent !== $tenant->parent_tenant_id && $parent !== null && auth()->check()) {
                Resource::addUsage($parent, $tenant, auth()->user());
            }
            if ($parent !== null) {
                PlanAssignments::ensureAssignableToChildTenant($plan, $parent, $tenant);
                PlanAssignments::syncTenantReservations($parent, $tenant, $plan);
            }
            PlanAssignments::ensureTenantContract($tenant, $plan);
        }
    }

    public function deleted(Tenant $tenant): void
    {
        if (($parent = $tenant->parentTenant()->first()) !== null) {
            Resource::removeUsage($parent, $tenant);
        }
    }
}
