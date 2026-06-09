<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string $model_type
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection<PlanResource> $plans
 */
class Resource extends Model
{
    use HasUlids;

    public $guarded = [];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class)
            ->withPivot(['limit'])
            ->using(PlanResource::class);
    }

    public function limit(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->pivot->limit
        );
    }
}
