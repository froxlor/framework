<?php

namespace Froxlor\Core\Policies\Concerns;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Traits\TenantAccessPermission;

/**
 * Provides the shared permission lookup used by core policies.
 *
 * Core permissions can be granted globally through user roles or locally through
 * tenant/environment pivot roles. Policies should call this helper whenever an
 * action can be authorized by either global privileges or scoped membership.
 */
trait ResolvesScopedPermissions
{
    use TenantAccessPermission;

    /**
     * Check whether a user has a permission globally, for a tenant, or for an environment.
     *
     * The lookup is intentionally read-only and returns false for invalid or
     * missing scoped memberships instead of throwing, which makes it suitable
     * for Gate/Policy decisions. If a tenant or environment scope is provided,
     * the user must first be allowed to access that tenant. This prevents broad
     * global permissions from being used against unrelated tenant trees.
     *
     * If both tenant and environment are provided, the environment must belong
     * to that tenant before environment-level permissions are used.
     *
     * @param User $user User being authorized.
     * @param string $permission Permission key to check, for example "tenants.update".
     * @param Tenant|null $tenant Optional tenant scope for tenant pivot permissions.
     * @param Environment|null $environment Optional environment scope for environment pivot permissions.
     * @return bool True when the permission is available globally or in the given scope.
     */
    protected function hasScopedPermission(
        User $user,
        string $permission,
        ?Tenant $tenant = null,
        ?Environment $environment = null,
    ): bool {
        $tenant ??= $environment?->tenant;

        if ($tenant !== null && !$this->userTenantAllowed($user, $tenant)) {
            return false;
        }

        if ($user->hasPermission($permission)) {
            return true;
        }

        if ($environment !== null) {
            if ($tenant !== null && $environment->tenant_id !== $tenant->id) {
                return false;
            }
            return $environment->userHasPermission($user, $permission);
        }

        if ($tenant !== null) {
            return $tenant->userHasPermission($user, $permission);
        }

        return false;
    }
}
