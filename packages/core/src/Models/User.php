<?php

namespace Froxlor\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsTenantResource;
use Froxlor\Core\Services\Traits\CanDelegatePermissions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $company_name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $email_verified_at
 * @property-read string $acronym
 * @property-read string $avatar
 * @property-read string $name
 * @property Collection<TenantUser> $tenants
 * @property Collection<EnvironmentUser> $environments
 * @property Collection<RoleUser> $roles
 * @property Collection<EnvUsage> $envUsages
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Froxlor\Core\Database\Factories\UserFactory> */
    use HasFactory, HasUlids, Notifiable, HasApiTokens, IsResource, IsTenantResource, IsEnvironmentResource, HasPermissions, CanDelegatePermissions, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
        'acronym',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['role_id', 'plan_id'])
            ->using(TenantUser::class);
    }

    public function environments(): BelongsToMany
    {
        return $this->belongsToMany(Environment::class)
            ->withPivot(['role_id', 'plan_id'])
            ->using(EnvironmentUser::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->using(RoleUser::class);
    }

    public function envUsages(): HasMany
    {
        return $this->hasMany(EnvUsage::class);
    }

    public function acronym(): Attribute
    {
        return Attribute::get(fn() => strtoupper(collect(explode(' ', $this->name))->map(fn($part) => $part[0])->take(2)->join('')));
    }

    public function avatar(): Attribute
    {
        return Attribute::get(fn() => 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=identicon&s=64');
    }

    public function name(): Attribute
    {
        return Attribute::get(fn() => trim($this->first_name . ' ' . $this->last_name) ?: $this->company_name ?: 'N/A');
    }

    /**
     * global (environment independent) permissions
     *
     * @param string|array $permission
     * @return bool
     */
    public function hasPermission(string|array $permission): bool
    {
        $possible_permissions = Permission::generatePermissionPath($permission);
        return $this->roles()->whereHas('permissions', function ($query) use ($possible_permissions) {
            $query->whereIn('key', $possible_permissions);
        })->exists();
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
            // special permissions for users
            ['key' => 'users.show-current', 'name' => 'View myself'],
            ['key' => 'users.update-current', 'name' => 'Update myself'],
            ['key' => 'users.show-subusers', 'name' => 'View my users'],
        ];
    }
}
