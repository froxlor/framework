<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Plan;
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
}
