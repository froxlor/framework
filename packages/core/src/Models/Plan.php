<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Core\Services\Traits\IsTenantResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string $type
 * @property string $name
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Tenant|null $tenant
 * @property Collection<Environment> $environments
 * @property Collection<PlanResource> $resources
 */
class Plan extends Model
{
    use HasUlids, IsResource, IsTenantResource, HasPermissions {
        HasPermissions::getAllPermissions as protected getBasePermissions;
    }

    protected $guarded = [];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot(['limit'])
            ->using(PlanResource::class);
    }

    /**
     * Return plan CRUD permissions plus resource-assignment permissions.
     *
     * Plan resources are managed as a separate API surface, mirroring role
     * permissions, because changing a plan's resource limits is a security-sensitive
     * quota change rather than a simple plan metadata update.
     */
    public static function getAllPermissions(): array
    {
        return [
            ...self::getBasePermissions(),
            ['key' => 'plans.resources.*', 'name' => 'Manage plan resources'],
            ['key' => 'plans.resources.index', 'name' => 'View plan resources'],
            ['key' => 'plans.resources.store', 'name' => 'Assign plan resources'],
            ['key' => 'plans.resources.destroy', 'name' => 'Remove plan resources'],
        ];
    }

    /**
     * Limit the query to plans usable for environments.
     */
    public function scopeEnvironment(Builder $query): Builder
    {
        return $query->where('type', 'environment');
    }

    /**
     * Limit the query to plans usable by the given tenant.
     *
     * Global plans are available to every tenant. Tenant-bound plans are only
     * available inside their owning tenant scope.
     */
    public function scopeAvailableForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where(function (Builder $query) use ($tenant) {
            $query->whereNull('tenant_id')
                ->orWhere('tenant_id', $tenant->id);
        });
    }

    /**
     * Check whether this plan can be assigned to tenant users.
     */
    public function isTenantPlan(): bool
    {
        return $this->type === 'tenant';
    }

    /**
     * Check whether this plan can be assigned to an environment.
     */
    public function isEnvironmentPlan(): bool
    {
        return $this->type === 'environment';
    }

    /**
     * Check whether this plan is available in the given tenant context.
     */
    public function isAvailableForTenant(Tenant $tenant): bool
    {
        return $this->tenant_id === null || $this->tenant_id === $tenant->id;
    }
}
