<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.index');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->hasTenantPermission($user, $tenant, 'tenants.index');
    }

    public function create(User $user, ?Tenant $parentTenant = null): bool
    {
        if ($parentTenant !== null) {
            return $this->hasTenantPermission($user, $parentTenant, 'tenants.store');
        }

        return $user->hasPermission('tenants.store');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->hasTenantPermission($user, $tenant, 'tenants.update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->hasTenantPermission($user, $tenant, 'tenants.destroy');
    }

    private function hasTenantPermission(User $user, Tenant $tenant, string $permission): bool
    {
        if ($user->hasPermission($permission)) {
            return true;
        }

        if (!$tenant->users()->where('users.id', $user->id)->exists()) {
            return false;
        }

        return $tenant->userHasPermission($user, $permission);
    }
}
