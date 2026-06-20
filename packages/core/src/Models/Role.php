<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Tenant|null $tenant
 * @property Collection<User> $users
 * @property Collection<PermissionRole> $permissions
 */
class Role extends Model
{
    use HasUlids, IsResource, HasPermissions;

    protected $guarded = [];

    protected $appends = ['members_count'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)
            ->withPivot('inheritable')
            ->using(PermissionRole::class);
    }

    public function membersCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->users()->count()
        );
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
            // role-permissions permissions for this resource
            ['key' => $basePermKey . '.permissions.*', 'name' => 'Manage permissions in ' . $basePermKey],
            ['key' => $basePermKey . '.permissions.available', 'name' => 'List available permissions'],
            ['key' => $basePermKey . '.permissions.index', 'name' => 'View permissions in ' . $basePermKey],
            ['key' => $basePermKey . '.permissions.store', 'name' => 'Add permissions to ' . $basePermKey],
            ['key' => $basePermKey . '.permissions.destroy', 'name' => 'Delete permissions in ' . $basePermKey],
            // role-users permissions for this resource
            ['key' => $basePermKey . '.users.*', 'name' => 'Manage users in ' . $basePermKey],
            ['key' => $basePermKey . '.users.index', 'name' => 'View users in ' . $basePermKey],
            // tenant based roles
            ['key' => 'tenants.' . $basePermKey . '.*', 'name' => 'Manage tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.index', 'name' => 'View tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.store', 'name' => 'Create tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.update', 'name' => 'Update tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.destroy', 'name' => 'Delete tenant ' . $basePermKey],
            // tenant role-permissions permissions for this resource
            ['key' => 'tenants.' . $basePermKey . '.permissions.*', 'name' => 'Manage permissions in tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.permissions.index', 'name' => 'View permissions in tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.permissions.store', 'name' => 'Add permissions to tenant ' . $basePermKey],
            ['key' => 'tenants.' . $basePermKey . '.permissions.destroy', 'name' => 'Delete permissions in tenant ' . $basePermKey],
        ];
    }
}
