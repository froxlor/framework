<?php

namespace Froxlor\Core\Models;

use Exception;
use Froxlor\Core\Observers\TenantUserObserver;
use Froxlor\Core\Support\Resource;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $role_id
 * @property string|null $plan_id
 * @property Tenant $tenant
 * @property User $user
 * @property Role $role
 * @property Plan|null $plan
 */
#[ObservedBy(TenantUserObserver::class)]
class TenantUser extends Pivot
{
    use HasUlids;

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
        return $this->role->permissions()
            ->whereIn('key', $possible_permissions)
            ->exists();
    }

    /**
     * @param string $resource
     * @return bool
     * @throws Exception
     */
    public function hasResourceAvailable(string $resource): bool
    {
        /** @var Plan $plan */
        $plan = $this->plan;
        if (is_null($plan)) {
            // use tenant plan if no users-plan is set
            $plan = $this->tenant->plan;
        }
        /** @var Resource $resource_to_check */
        $resource_to_check = $plan->resources()->where('key', $resource)->first();
        if (empty($resource_to_check)) {
            // don't have this resource assigned to plan at all
            return false;
        }
        $max = $resource_to_check->pivot->limit;
        if (empty($max)) {
            // not allowed (0 value)
            return false;
        } elseif ($max == -1) {
            // unlimited
            return true;
        } else {
            // limit set - check for already used resources
            $used = Resource::getUsage($this->tenant, $resource_to_check->model_type, $this->user);
            return $used < $max;
        }
    }
}
