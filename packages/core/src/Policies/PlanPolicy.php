<?php

namespace Froxlor\Core\Policies;

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

    public function resourceViewAny(User $user, Plan $plan): bool
    {
        return $plan->tenant_id === null
            && $user->hasPermission('plans.resources.index');
    }

    public function availableResourcesViewAny(User $user): bool
    {
        return $user->hasPermission('plans.resources.index');
    }

    public function resourceCreate(User $user, Plan $plan): bool
    {
        return $plan->tenant_id === null
            && $user->hasPermission('plans.resources.store');
    }

    public function resourceDelete(User $user, Plan $plan): bool
    {
        return $plan->tenant_id === null
            && $user->hasPermission('plans.resources.destroy');
    }

    public function usersViewAny(User $user, Plan $plan): bool
    {
        return $plan->tenant_id === null
            && $user->hasPermission('plans.users.index');
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

    public function tenantResourceViewAny(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.resources.index', $tenant);
    }

    public function tenantResourceCreate(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.resources.store', $tenant);
    }

    public function tenantResourceDelete(User $user, Plan $plan, Tenant $tenant): bool
    {
        if ($plan->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.plans.resources.destroy', $tenant);
    }

}
