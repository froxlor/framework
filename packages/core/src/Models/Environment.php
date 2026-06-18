<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Observers\EnvironmentObserver;
use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $tenant_id
 * @property string $plan_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Tenant $tenant
 * @property Plan $plan
 * @property Collection<NodeEnvironment> $nodes
 * @property Collection<EnvironmentUser> $users
 * @property Collection<EnvUsage> $envUsages
 */
#[ObservedBy(EnvironmentObserver::class)]
class Environment extends Model
{
    use HasUlids, IsResource, HasPermissions;

    protected $guarded = [];

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class,
            'node_environments',
            'environment_id',
            'node_id'
        )->withPivot(['unix_name', 'guid'])->using(NodeEnvironment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'plan_id'])
            ->using(EnvironmentUser::class);
    }

    public function envUsages(): HasMany
    {
        return $this->hasMany(EnvUsage::class);
    }

    public function envUsageList(): Attribute
    {
        return Attribute::make(
            get: fn() => EnvUsage::query()->select('resource_key', DB::raw('COUNT(DISTINCT resource_id) as total'))
                ->where('environment_id', $this->id)
                ->groupBy('resource_key')
                ->get()
                ->pluck('total', 'resource_key')
                ->toArray()
        );
    }

    /**
     * @throws UnknownEnvironmentUserException
     */
    public function userHasResourceAvailable(User $user, string $resource): bool
    {
        /** @var EnvironmentUser $pivot */
        $pivot = $this->users()->where('user_id', $user->id)->first();
        if (empty($pivot)) {
            throw new UnknownEnvironmentUserException("Unknown environment users");
        }
        return $pivot->pivot->hasResourceAvailable($resource);
    }

    /**
     * Check if the users has the given permission.
     *
     * @param User $user
     * @param string|array $permission
     * @return bool
     */
    public function userHasPermission(User $user, string|array $permission): bool
    {
        $environmentUser = $this->users()
            ->where('users.id', $user->id)
            ->first();

        if ($environmentUser !== null && $environmentUser->pivot->hasPermission($permission)) {
            return true;
        }

        return false;
    }

    /**
     * return array of permissions provided for the associated object
     *
     * @return array
     */
    public static function getAllPermissions(): array
    {
        // get base key (e.g. 'users' for User::class etc.)
        $basePermKey = self::getResourceKey();

        // add some global additional permissions here that are being used without context of a resource
        // note, environments are always tenant-based
        return [
            // standard permissions for this resource
            ['key' => 'tenants.' . $basePermKey . '.*', 'name' => 'Manage ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.index', 'name' => 'View ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.store', 'name' => 'Create ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.update', 'name' => 'Update ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.destroy', 'name' => 'Delete ' . $basePermKey],
            // environment-users permissions
            ['key' => 'tenants.' . $basePermKey . '.users.*', 'name' => 'Manage users in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.users.index', 'name' => 'View users in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.users.store', 'name' => 'Create users in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.users.update', 'name' => 'Update users in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.users.destroy', 'name' => 'Delete users in ' . $basePermKey],
            // environment-plans permissions
            ['key' => 'tenants.' . $basePermKey . '.plans.*', 'name' => 'Manage plans in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.plans.index', 'name' => 'View plans in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.plans.store', 'name' => 'Create plans in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.plans.update', 'name' => 'Update plans in ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.plans.destroy', 'name' => 'Delete plans in ' . $basePermKey],
        ];
    }
}
