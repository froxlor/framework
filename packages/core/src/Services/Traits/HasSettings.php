<?php

namespace Froxlor\Core\Services\Traits;

use Exception;
use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

trait HasSettings
{
    public function getAllSettings(): Builder
    {
        return SettingModel::query()->select('*')
            ->joinSub(
                SettingModel::query()->selectRaw("
                    `category`, `key`,
                    MAX(CASE
                        WHEN settingable_type IS NULL AND settingable_id IS NULL THEN 1
                        WHEN settingable_type IS NOT NULL AND settingable_id IS NULL THEN 2
                        ELSE 3
                    END) AS max_weight
                ")->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereNull('settingable_type')->whereNull('settingable_id');
                    })
                        ->orWhere(function ($query) {
                            $query->where('settingable_type', self::class)->whereNull('settingable_id');
                        })
                        ->orWhere(function ($query) {
                            $query->where('settingable_type', self::class)->where('settingable_id', $this->id);
                        });
                })
                    ->groupBy('category', 'key'), 'max_weights', function (JoinClause $join) {
                $join->on('settings.category', '=', 'max_weights.category')
                    ->on('settings.key', '=', 'max_weights.key')
                    ->whereRaw("
                    (CASE
                        WHEN settings.settingable_type IS NULL AND settings.settingable_id IS NULL THEN 1
                        WHEN settings.settingable_type IS NOT NULL AND settings.settingable_id IS NULL THEN 2
                        ELSE 3
                    END) = max_weights.max_weight
                    ");
            }
            )->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('settingable_type')->whereNull('settingable_id');
                })
                    ->orWhere(function ($query) {
                        $query->where('settingable_type', self::class)->whereNull('settingable_id');
                    })
                    ->orWhere(function ($query) {
                        $query->where('settingable_type', self::class)->where('settingable_id', $this->id);
                    });
            });
    }

    /**
     * returns a resource-specific setting
     *
     * @param string $settings_path
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public function getSetting(string $settings_path, mixed $default = null): mixed
    {
        return Setting::getForModel($this, $settings_path, $default);
    }

    /**
     * adds a resource-specific setting
     *
     * @param string $settings_path
     * @param mixed $value
     * @param mixed|null $default
     * @param string $type
     * @param array $properties
     * @return void
     * @throws Exception
     */
    public function addSetting(string $settings_path, mixed $value, mixed $default = null, string $type = 'string', array $properties = []): void
    {
        Setting::add($settings_path, $value, $default, $type, $properties, self::class, $this->id);
    }

    /**
     * adds/updates a resource-specific setting
     *
     * @param string $settings_path
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function setSetting(string $settings_path, mixed $value): mixed
    {
        return Setting::setValueForModel($this, $settings_path, $value);
    }

    /**
     * adds a resource-type-specific setting
     *
     * @param string $settings_path
     * @param mixed $value
     * @param mixed|null $default
     * @param string $type
     * @param array $properties
     * @return void
     * @throws Exception
     */
    public static function addTypeSetting(string $settings_path, mixed $value, mixed $default = null, string $type = 'string', array $properties = []): void
    {
        Setting::add($settings_path, $value, $default, $type, $properties, self::class);
    }

    /**
     * sets/updates a resource-type-specific setting
     *
     * @param string $settings_path
     * @param mixed $value
     * return void
     * @throws Exception
     */
    public static function setTypeSetting(string $settings_path, mixed $value): void
    {
        Setting::setValueForType(self::class, $settings_path, $value);
    }

    /**
     * @param string $settings_path
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public static function getTypeSetting(string $settings_path, mixed $default = null): mixed
    {
        return Setting::getValueForType(self::class, $settings_path, $default);
    }
}
