<?php

namespace Froxlor\Core\Services\Traits;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;

trait TenantAccessPermission
{

    /**
     * @param User $request_user
     * @param Tenant $target_tenant
     * @return bool
     */
    protected function userTenantAllowed(User $request_user, Tenant $target_tenant): bool
    {
        // check whether the current tenant is a sub-tenant of the users tenant
        $isSubTenantOfUserTenant = false;
        $request_user->tenants()->each(function (Tenant $tenant) use ($target_tenant, &$isSubTenantOfUserTenant) {
            if (in_array($target_tenant->id, $tenant->getSubTenantsIds())) {
                $isSubTenantOfUserTenant = true;
                // break out of each()
                return false;
            }
            // continue each()
            return true;
        });
        // either I'm a users of the tenant or it is a subtenant of mine
        if (!$target_tenant->users->contains($request_user) && !$isSubTenantOfUserTenant) {
            return false;
        }
        return true;
    }
}
