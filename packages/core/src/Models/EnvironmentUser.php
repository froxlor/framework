<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\CanDelegatePermissions;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $user_id
 * @property string|null $role_id
 * @property string|null $plan_id
 * @property Environment $environment
 * @property User $user
 * @property Role|null $role
 * @property Plan|null $plan
 */
#[\Illuminate\Database\Eloquent\Attributes\ObservedBy(\Froxlor\Core\Observers\EnvironmentUserObserver::class)]
class EnvironmentUser extends Pivot
{
    use \Froxlor\Core\Services\Traits\SavesWithinQuotaTransaction;
    use HasUlids, CanDelegatePermissions;

    public $timestamps = true;

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
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
     * @param string $resource
     * @return bool
     * @throws \Exception
     */
    public function hasResourceAvailable(string $resource): bool
    {
        return \Froxlor\Core\Support\Quota::environmentAvailable($this->environment, $resource, $this->user);
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
}
