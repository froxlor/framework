<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class PlanPolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('plans.index');
    }

    public function view(User $user, Plan $plan): bool
    {
        if ($plan->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.plans.index', $plan->tenant);
        }

        return $user->hasPermission('plans.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('plans.store');
    }

    public function update(User $user, Plan $plan): bool
    {
        if ($plan->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.plans.update', $plan->tenant);
        }

        return $user->hasPermission('plans.update');
    }

    public function delete(User $user, Plan $plan): bool
    {
        if ($plan->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.plans.destroy', $plan->tenant);
        }

        return $user->hasPermission('plans.destroy');
    }

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.plans.index', $tenant);
    }

    public function tenantView(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.index', $tenant);
    }

    public function tenantCreate(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.plans.store', $tenant);
    }

    public function tenantUpdate(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.update', $tenant);
    }

    public function tenantDelete(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.destroy', $tenant);
    }

    public function tenantEnvViewAny(User $user, Tenant $tenant, Environment $environment): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.plans.index', $tenant, $environment);
    }

    public function tenantEnvView(User $user, Plan $plan, Tenant $tenant, Environment $environment): bool
    {
        if ($plan->tenant_id !== $tenant->id || $plan->type !== 'environment') {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.plans.index', $tenant, $environment);
    }

    public function tenantEnvCreate(User $user, Tenant $tenant, Environment $environment): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.plans.store', $tenant, $environment);
    }

    public function tenantEnvUpdate(User $user, Plan $plan, Tenant $tenant, Environment $environment): bool
    {
        if ($plan->tenant_id !== $tenant->id || $plan->type !== 'environment') {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.plans.update', $tenant, $environment);
    }

    public function tenantEnvDelete(User $user, Plan $plan, Tenant $tenant, Environment $environment): bool
    {
        if ($plan->tenant_id !== $tenant->id || $plan->type !== 'environment') {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.environments.plans.destroy', $tenant, $environment);
    }
}
