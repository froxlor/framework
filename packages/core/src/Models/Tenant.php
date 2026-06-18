<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $parent_tenant_id
 * @property string $plan_id
 * @property string $name
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection<Environment> $environments
 * @property Plan $plan
 * @property Collection<Role> $roles
 * @property Collection<TenantUser> $users
 * @property Collection<TenantUsage> $tenantUsages
 * @property Collection<Tenant> $subTenants
 * @property Collection<Tenant> $allSubTenants
 * @property Tenant|null $parentTenant
 */
class Tenant extends Model
{
    use HasUlids, IsResource, HasPermissions;

    public $guarded = [];

    public $appends = [
        'users_count',
        'all_users_count',
        'sub_tenants_count',
        'all_sub_tenants_count',
    ];

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function ownedNodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class)
            ->withPivot('inheritable')
            ->withTimestamps();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'plan_id'])
            ->using(TenantUser::class);
    }

    public function tenantUsages(): HasMany
    {
        return $this->hasMany(TenantUsage::class);
    }

    public function tenantUsageList(): Attribute
    {
        return Attribute::make(
            get: fn() => TenantUsage::query()->select('resource_key', DB::raw('COUNT(DISTINCT resource_id) as total'))
                ->where('tenant_id', $this->id)
                ->groupBy('resource_key')
                ->get()
                ->pluck('total', 'resource_key')
                ->toArray()
        );
    }

    public function subTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'parent_tenant_id', 'id');
    }

    /**
     * Limit the query to tenants without a parent tenant.
     *
     * Root tenants are the top-level entry points for tenant trees and are
     * created during installation/bootstrap rather than through child-tenant
     * management flows.
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_tenant_id');
    }

    /**
     * Limit the query to direct children of the given tenant.
     */
    public function scopeChildrenOf(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('parent_tenant_id', $tenant->id);
    }

    /**
     * Limit the query to tenants contained in the given tenant tree.
     *
     * This currently resolves the tree in PHP because tenant trees are expected
     * to be shallow in normal control-panel usage. The method keeps callers away
     * from duplicating hierarchy traversal and can later be replaced internally
     * by a recursive SQL implementation if needed.
     */
    public function scopeInTreeOf(Builder $query, Tenant $tenant, bool $includeSelf = true): Builder
    {
        return $query->whereIn('id', $tenant->descendantIds($includeSelf));
    }

    /**
     * Return all descendants of this tenant in breadth-first order.
     *
     * The returned collection contains children, grandchildren, and deeper
     * descendants, but never the current tenant itself.
     *
     * @return Collection<int, Tenant>
     */
    public function allSubTenants(): Collection
    {
        $all = new Collection();
        $queue = $this->subTenants()->get();

        while ($queue->isNotEmpty()) {
            $all = $all->merge($queue);
            $queue = $queue->map->subTenants->flatten();
        }
        return $all;
    }

    /**
     * Return all descendant tenant IDs.
     *
     * @param bool $includeSelf Include the current tenant ID in the returned
     * list. This is useful for queries that should operate on the whole tree,
     * including its root tenant.
     * @return array<int, string>
     */
    public function descendantIds(bool $includeSelf = false): array
    {
        $tenantIds = $this->allSubTenants()->pluck('id');

        if ($includeSelf) {
            $tenantIds->prepend($this->id);
        }

        return $tenantIds->values()->all();
    }

    public function getAllUsers()
    {
        return User::whereHas('tenants', function ($q) {
            $q->whereIn('tenants.id', $this->descendantIds(true));
        })->with(['tenants']);
    }

    /**
     * @return BelongsTo
     */
    public function parentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'parent_tenant_id', 'id');
    }

    /**
     * Check whether this tenant is an ancestor of the given tenant.
     */
    public function isAncestorOf(Tenant $tenant): bool
    {
        return in_array($tenant->id, $this->descendantIds(), true);
    }

    /**
     * Check whether this tenant is below the given tenant in the tenant tree.
     */
    public function isDescendantOf(Tenant $tenant): bool
    {
        return $tenant->isAncestorOf($this);
    }

    /**
     * Check whether this tenant tree contains the given tenant.
     *
     * @param Tenant $tenant Tenant to look for.
     * @param bool $includeSelf Treat the current tenant as part of its own tree.
     */
    public function containsTenant(Tenant $tenant, bool $includeSelf = false): bool
    {
        if ($includeSelf && $this->id === $tenant->id) {
            return true;
        }

        return $this->isAncestorOf($tenant);
    }

    /**
     * Backwards-compatible wrapper for older call sites.
     */
    public function isParentToTenant(Tenant $tenant): bool
    {
        return $this->isAncestorOf($tenant);
    }

    public function usersCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->users()->count(),
        );
    }

    public function allUsersCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAllUsers()->count(),
        );
    }

    protected function subTenantsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->subTenants()->pluck('id')->count(),
        );
    }

    protected function allSubTenantsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->allSubTenants()->pluck('id')->count(),
        );
    }

    /**
     * @throws UnknownTenantUserException
     */
    public function userHasResourceAvailable(User $user, string $resource): bool
    {
        /** @var TenantUser $pivot */
        $pivot = $this->users()->where('user_id', $user->id)->first();
        if (empty($pivot)) {
            throw new UnknownTenantUserException("Unknown tenant users");
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
        $tenantUser = $this->users()
            ->where('users.id', $user->id)
            ->first();

        if ($tenantUser !== null && $tenantUser->pivot->hasPermission($permission)) {
            return true;
        }
        return false;
    }

    public static function getAllPermissions(): array
    {
        // get base key (e.g. 'users' for User::class etc.)
        $basePermKey = self::getResourceKey();

        // add some global additional permissions here that are being used without context of a resource
        return [
            // standard permissions for this resource
            ['key' => $basePermKey . '.*', 'name' => 'Manage ' . $basePermKey],
            ['key' => $basePermKey . '.index', 'name' => 'View ' . $basePermKey],
            ['key' => $basePermKey . '.store', 'name' => 'Create ' . $basePermKey],
            ['key' => $basePermKey . '.update', 'name' => 'Update ' . $basePermKey],
            ['key' => $basePermKey . '.destroy', 'name' => 'Delete ' . $basePermKey],
            // tenant-plans permissions
            ['key' => $basePermKey . '.plans.*', 'name' => 'Manage plans in ' . $basePermKey],
            ['key' => $basePermKey . '.plans.index', 'name' => 'View plans in ' . $basePermKey],
            ['key' => $basePermKey . '.plans.store', 'name' => 'Create plans in ' . $basePermKey],
            ['key' => $basePermKey . '.plans.update', 'name' => 'Update plans in ' . $basePermKey],
            ['key' => $basePermKey . '.plans.destroy', 'name' => 'Delete plans in ' . $basePermKey],
            // tenants users
            ['key' => $basePermKey . '.users.*', 'name' => 'Manage ' . $basePermKey . ' users'],
            ['key' => $basePermKey . '.users.index', 'name' => 'View ' . $basePermKey . ' users'],
            ['key' => $basePermKey . '.users.store', 'name' => 'Create ' . $basePermKey . ' users'],
            ['key' => $basePermKey . '.users.update', 'name' => 'Update ' . $basePermKey . ' users'],
            ['key' => $basePermKey . '.users.destroy', 'name' => 'Delete ' . $basePermKey . ' users'],
            // tenant nodes
            ['key' => $basePermKey . '.nodes.*', 'name' => 'Manage ' . $basePermKey . ' nodes'],
            ['key' => $basePermKey . '.nodes.index', 'name' => 'View ' . $basePermKey . ' nodes'],
            ['key' => $basePermKey . '.nodes.store', 'name' => 'Create ' . $basePermKey . ' nodes'],
            ['key' => $basePermKey . '.nodes.update', 'name' => 'Update ' . $basePermKey . ' nodes'],
            ['key' => $basePermKey . '.nodes.destroy', 'name' => 'Delete ' . $basePermKey . ' nodes'],
        ];
    }

    protected function initials(): Attribute
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $initials .= strtoupper($part[0]);
            if (strlen($initials) >= 2) {
                break;
            }
        }
        return Attribute::get(fn() => $initials ?: '?');
    }
}
