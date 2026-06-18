<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class NodePolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('nodes.index');
    }

    public function view(User $user, Node $node): bool
    {
        if ($node->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.nodes.index', $node->tenant);
        }

        return $user->hasPermission('nodes.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('nodes.store');
    }

    public function update(User $user, Node $node): bool
    {
        if ($node->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.nodes.update', $node->tenant);
        }

        return $user->hasPermission('nodes.update');
    }

    public function delete(User $user, Node $node): bool
    {
        if ($node->tenant !== null) {
            return $this->hasScopedPermission($user, 'tenants.nodes.destroy', $node->tenant);
        }

        return $user->hasPermission('nodes.destroy');
    }

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.nodes.index', $tenant);
    }

    public function tenantView(User $user, Node $node, Tenant $tenant): bool
    {
        if (!$node->isAvailableForTenant($tenant)) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.nodes.index', $tenant);
    }

    public function tenantCreate(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.nodes.store', $tenant);
    }

    public function tenantUpdate(User $user, Node $node, Tenant $tenant): bool
    {
        if ($node->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.nodes.update', $tenant);
    }

    public function tenantDelete(User $user, Node $node, Tenant $tenant): bool
    {
        if ($node->tenant_id !== $tenant->id) {
            return false;
        }

        return $this->hasScopedPermission($user, 'tenants.nodes.destroy', $tenant);
    }
}
