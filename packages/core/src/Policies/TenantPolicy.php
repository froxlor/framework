<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class TenantPolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.index');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.index', $tenant);
    }

    public function create(User $user, ?Tenant $parentTenant = null): bool
    {
        if ($parentTenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.store', $parentTenant);
        }

        return $user->hasPermission('tenants.store');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.update', $tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.destroy', $tenant);
    }
}
