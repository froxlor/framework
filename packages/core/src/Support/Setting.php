<?php

namespace Froxlor\Core\Support;

use Exception;
use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Services\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class Setting
{
    public static function get(string $path, mixed $default = null): mixed
    {
        $s = self::parsePath($path);

        $setting = SettingModel::query()
            ->select('value', 'type')
            ->where('category', $s['category'])
            ->where('key', $s['key'])
            ->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function set(string $path, mixed $value, string $type = 'text', mixed $default = null): SettingModel
    {
        $s = self::parsePath($path);

        return SettingModel::updateOrCreate(
            self::baseConditions($s, null, null, $type),
            [
                'value' => $value,
                'default_value' => $default,
            ]
        );
    }

    public static function getValueForType(string $resourceType, string $path, mixed $default = null): mixed
    {
        $s = self::parsePath($path);

        $setting = SettingModel::query()
            ->select('value')
            ->where('category', $s['category'])
            ->where('key', $s['key'])
            ->where('settingable_type', $resourceType)
            ->whereNull('settingable_id')
            ->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function setValueForType(string $resourceType, string $path, mixed $value, string $type = 'text'): SettingModel
    {
        self::assertHasSettingsTrait($resourceType);

        $s = self::parsePath($path);

        return SettingModel::updateOrCreate(
            self::baseConditions($s, $resourceType, null),
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    public static function getForModel(Model $resource, string $path, mixed $default = null): mixed
    {
        if (!self::usesSettingsTrait($resource)) {
            return self::get($path, $default);
        }

        $s = self::parsePath($path);

        $setting = $resource->getAllSettings()
            ->select('value')
            ->where('settings.category', $s['category'])
            ->where('settings.key', $s['key'])
            ->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function setValueForModel(Model $resource, string $path, mixed $value, string $type = 'text'): SettingModel
    {
        self::assertHasSettingsTrait($resource);

        $s = self::parsePath($path);

        return SettingModel::updateOrCreate(
            self::baseConditions($s, $resource::class, $resource->id),
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    public static function add(string $path, mixed $value, mixed $default = null, string $type = 'string', array $properties = [], ?string $settingableType = null, ?string $settingableId = null): void
    {
        $s = self::parsePath($path);

        $data = [
            'category' => $s['category'],
            'key' => $s['key'],
            'value' => $value,
            'default_value' => $default,
            'type' => $type,
            'properties' => $properties,
        ];

        if ($settingableType && class_exists($settingableType)) {
            $data['settingable_type'] = $settingableType;
            $data['settingable_id'] = $settingableId;
        }

        SettingModel::query()->create($data);
    }

    public static function addFromArray(array $setting): void
    {
        $setting['category'] ??= 'general';

        if (empty($setting['key'])) {
            throw new Exception('No settings key given');
        }

        if (empty($setting['type'])) {
            throw new Exception('No settings type given');
        }

        self::add(
            path: "{$setting['category']}.{$setting['key']}",
            value: $setting['value'] ?? null,
            default: $setting['default'] ?? null,
            type: $setting['type'],
            properties: $setting['properties'] ?? [],
            settingableType: $setting['settingable_type'] ?? null,
            settingableId: $setting['settingable_id'] ?? null,
        );
    }

    private static function parsePath(string $path): array
    {
        $parts = explode('.', $path);

        if (count($parts) < 2) {
            throw new Exception("Invalid settings path: {$path}");
        }

        return [
            'category' => array_shift($parts),
            'key' => implode('.', $parts),
        ];
    }

    private static function castValue(mixed $value, ?string $type): mixed
    {
        return match ($type) {
            'bool' => (bool)$value,
            'integer' => intval($value),
            default => $value,
        };
    }

    private static function baseConditions(array $s, ?string $type, ?string $id, ?string $settingType = null): array
    {
        return array_filter([
            'category' => $s['category'],
            'key' => $s['key'],
            'settingable_type' => $type,
            'settingable_id' => $id,
            'type' => $settingType,
        ], fn($v) => $v !== null);
    }

    private static function usesSettingsTrait(object|string $class): bool
    {
        return in_array(HasSettings::class, class_uses_recursive($class));
    }

    private static function assertHasSettingsTrait(object|string $class): void
    {
        if (!self::usesSettingsTrait($class)) {
            throw new Exception('Resource does not support custom settings');
        }
    }
}
