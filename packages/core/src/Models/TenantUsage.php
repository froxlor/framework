<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $resource_key
 * @property string $resource_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Tenant $tenant
 * @property User $user
 */
class TenantUsage extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    protected $guarded = [];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): Attribute
    {
        $resource_fqcn = Relation::getMorphedModel($this->resource_key);
        if (empty($resource_fqcn)) {
            abort(404, 'Given resource-type could not be found');
        }
        return Attribute::make(
            get: fn() => $resource_fqcn::query()->find($this->resource_id)->first(),
        );
    }
}
