<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Policies\Concerns\ResolvesScopedPermissions;

class AuditLogPolicy
{
    use ResolvesScopedPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit-log.index');
    }

    public function tenantViewAny(User $user, Tenant $tenant): bool
    {
        return $this->hasScopedPermission($user, 'tenants.audit-log.index', $tenant);
    }

    public function tenantEnvViewAny(User $user, Tenant $tenant, Environment $environment): bool
    {
        return $this->hasScopedPermission($user, 'tenants.environments.audit-log.index', $tenant, $environment);
    }
}
