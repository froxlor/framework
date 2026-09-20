<?php

namespace Froxlor\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property string $id
 * @property MorphTo $settingable
 * @property string $category
 * @property string $key
 * @property string|null $owner_package
 * @property string|null $definition_key
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

    protected static function booted(): void
    {
        static::creating(function (self $setting): void {
            if (!$setting->owner_package || !$setting->definition_key) {
                throw new LogicException('Settings must be created through Froxlor\\Core\\Support\\Setting with a package source.');
            }
        });

        static::updating(function (self $setting): void {
            if ($setting->isDirty(['category', 'key', 'owner_package', 'definition_key'])) {
                \Froxlor\Core\Support\SettingRegistry::assertDefinitionIsImmutable($setting);
            }
        });
    }

    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }
}
