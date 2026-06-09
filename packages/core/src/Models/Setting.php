<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property MorphTo $settingable
 * @property string $category
 * @property string $key
 * @property mixed $value
 * @property mixed $default_value
 * @property mixed $type
 * @property array $properties
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Setting extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
        'default_value' => 'array',
        'properties' => 'array',
    ];

    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }
}
