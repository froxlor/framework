<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class EnvironmentPolicy
{
    use ResolvesScopedPermissions;

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.index', $tenant);
    }

    public function tenantView(User $user, Environment $environment, Tenant $tenant): bool
    {
        if ($environment->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.index', $tenant);
    }

    public function tenantCreate(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.store', $tenant);
    }

    public function tenantUpdate(User $user, Environment $environment, Tenant $tenant): bool
    {
        if ($environment->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.update', $tenant);
    }

    public function tenantDelete(User $user, Environment $environment, Tenant $tenant): bool
    {
        if ($environment->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.destroy', $tenant);
    }
}
