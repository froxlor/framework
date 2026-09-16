<?php

namespace Froxlor\Core\Models;

use Exception;
use Froxlor\Core\Observers\TenantUserObserver;
use Froxlor\Core\Services\Traits\CanDelegatePermissions;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string|null $role_id
 * @property string|null $plan_id
 * @property Tenant $tenant
 * @property User $user
 * @property Role|null $role
 * @property Plan|null $plan
 */
#[ObservedBy(TenantUserObserver::class)]
class TenantUser extends Pivot
{
    use \Froxlor\Core\Services\Traits\SavesWithinQuotaTransaction;
    use HasUlids, CanDelegatePermissions;

    public $timestamps = true;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * check a given permission on a users/environment combination
     *
     * @param string|array $permission
     * @return bool
     */
    public function hasPermission(string|array $permission): bool
    {
        $possible_permissions = Permission::generatePermissionPath($permission);
        return $this->role()->whereHas('permissions', fn ($query) => $query->whereIn('key', $possible_permissions))
            ->exists();
    }

    /**
     * @param string $resource
     * @return bool
     * @throws Exception
     */
    public function hasResourceAvailable(string $resource): bool
    {
        return \Froxlor\Core\Support\Quota::tenantAvailable($this->tenant, $resource, $this->user);
    }
}
