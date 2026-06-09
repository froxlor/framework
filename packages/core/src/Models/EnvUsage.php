<?php

namespace Froxlor\Core\Models;

use Froxlor\Core\Observers\EnvUsageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $user_id
 * @property string $resource_key
 * @property string $resource_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Environment $environment
 * @property User $user
 */
#[ObservedBy(EnvUsageObserver::class)]
class EnvUsage extends Pivot
{
    use HasUlids;

    protected $guarded = [];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
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
