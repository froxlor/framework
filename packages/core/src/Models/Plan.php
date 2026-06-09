<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Services\Traits\HasPermissions;
use Froxlor\Core\Services\Traits\IsResource;
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
    use HasUlids, IsResource, HasPermissions;

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
}
