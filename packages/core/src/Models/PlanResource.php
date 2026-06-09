<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $plan_id
 * @property string $resource_id
 * @property int $limit
 * @property Plan $plan
 * @property Resource $resource
 */
class PlanResource extends Pivot
{
    use HasUlids;

    public $timestamps = true;

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
