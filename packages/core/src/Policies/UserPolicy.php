<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class UserPolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.index');
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.store');
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.update');
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.destroy');
    }

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.users.index', $tenant);
    }

    public function tenantView(User $user, User $targetUser, Tenant $tenant): bool
    {
        if (!$this->tenantHasUser($tenant, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.users.index', $tenant);
    }

    public function tenantCreate(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.users.store', $tenant);
    }

    public function tenantUpdate(User $user, User $targetUser, Tenant $tenant): bool
    {
        if (!$this->tenantHasUser($tenant, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.users.update', $tenant);
    }

    public function tenantDelete(User $user, User $targetUser, Tenant $tenant): bool
    {
        if (!$this->tenantHasUser($tenant, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.users.destroy', $tenant);
    }

    public function tenantEnvViewAny(User $user, Tenant $tenant, Environment $environment): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.users.index', $tenant, $environment);
    }

    public function tenantEnvView(User $user, User $targetUser, Tenant $tenant, Environment $environment): bool
    {
        if (!$this->environmentHasUser($environment, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.users.index', $tenant, $environment);
    }

    public function tenantEnvCreate(User $user, Tenant $tenant, Environment $environment): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.users.store', $tenant, $environment);
    }

    public function tenantEnvUpdate(User $user, User $targetUser, Tenant $tenant, Environment $environment): bool
    {
        if (!$this->environmentHasUser($environment, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.users.update', $tenant, $environment);
    }

    public function tenantEnvDelete(User $user, User $targetUser, Tenant $tenant, Environment $environment): bool
    {
        if (!$this->environmentHasUser($environment, $targetUser)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.users.destroy', $tenant, $environment);
    }

    private function tenantHasUser(Tenant $tenant, User $user): bool
    {
        return $tenant->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    private function environmentHasUser(Environment $environment, User $user): bool
    {
        return $environment->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
