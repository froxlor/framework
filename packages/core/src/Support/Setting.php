<?php

namespace Froxlor\Core\Support;

use Exception;
use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Services\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use LogicException;

class Setting
{
    public static function get(string $path, mixed $default = null): mixed
    {
        $s = self::parsePath($path);

        try {
            $setting = self::findForScope($s, null, null, ['value', 'type']);
        } catch (QueryException) {
            // Settings are read during provider boot (e.g. PackageServiceProvider::isEnabled())
            // and from migrations, both of which can run before the settings table exists
            // (fresh install, migrate:fresh). Fall back to the default in that case.
            return $default;
        }

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function set(string $path, mixed $value, string $type = 'text', mixed $default = null, ?string $source = null): SettingModel
    {
        $s = self::parsePath($path);
        $setting = self::findForScope($s, null, null);

        if ($setting) {
            $setting->value = $value;
            $setting->save();

            return $setting;
        }

        self::assertSource($source, 'set');

        return self::createDefinition(
            s: $s,
            value: $value,
            default: $default,
            type: $type,
            properties: [],
            settingableType: null,
            settingableId: null,
            source: $source,
        );
    }

    public static function getValueForType(string $resourceType, string $path, mixed $default = null): mixed
    {
        $s = self::parsePath($path);
        $setting = self::findForScope($s, $resourceType, null, ['value']);

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function setValueForType(string $resourceType, string $path, mixed $value, string $type = 'text', ?string $source = null): SettingModel
    {
        self::assertHasSettingsTrait($resourceType);

        $s = self::parsePath($path);
        $setting = self::findForScope($s, $resourceType, null);

        if ($setting) {
            $setting->value = $value;
            $setting->save();

            return $setting;
        }

        self::assertSource($source, 'setValueForType');

        return self::createDefinition(
            s: $s,
            value: $value,
            default: null,
            type: $type,
            properties: [],
            settingableType: $resourceType,
            settingableId: null,
            source: $source,
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

    public static function setValueForModel(Model $resource, string $path, mixed $value, string $type = 'text', ?string $source = null): SettingModel
    {
        self::assertHasSettingsTrait($resource);

        $s = self::parsePath($path);
        $setting = self::findForScope($s, $resource::class, (string)$resource->id);

        if ($setting) {
            $setting->value = $value;
            $setting->save();

            return $setting;
        }

        self::assertSource($source, 'setValueForModel');

        return self::createDefinition(
            s: $s,
            value: $value,
            default: null,
            type: $type,
            properties: [],
            settingableType: $resource::class,
            settingableId: (string)$resource->id,
            source: $source,
        );
    }

    public static function add(
        string $path,
        mixed $value,
        mixed $default = null,
        string $type = 'string',
        array $properties = [],
        ?string $settingableType = null,
        ?string $settingableId = null,
        ?string $source = null,
    ): SettingModel {
        $s = self::parsePath($path);
        self::assertSource($source, 'add');

        $settingableType = $settingableType && class_exists($settingableType) ? $settingableType : null;
        $setting = self::findForScope($s, $settingableType, $settingableId);
        $definitionKey = self::registerDefinition($s, $source);

        if ($setting) {
            $setting->fill([
                'value' => $value,
                'default_value' => $default,
                'type' => $type,
                'properties' => $properties,
            ]);
            $setting->save();

            return $setting;
        }

        return self::createDefinition(
            s: $s,
            value: $value,
            default: $default,
            type: $type,
            properties: $properties,
            settingableType: $settingableType,
            settingableId: $settingableId,
            source: $source,
            definitionKey: $definitionKey,
        );
    }

    public static function addFromArray(array $setting, ?string $source = null): void
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
            default: $setting['default'] ?? $setting['default_value'] ?? null,
            type: $setting['type'],
            properties: $setting['properties'] ?? [],
            settingableType: $setting['settingable_type'] ?? null,
            settingableId: $setting['settingable_id'] ?? null,
            source: $source,
        );
    }

    private static function parsePath(string $path): array
    {
        $parts = explode('.', $path, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new Exception("Invalid settings path: {$path}");
        }

        return [
            'category' => $parts[0],
            'key' => $parts[1],
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

    private static function findForScope(array $s, ?string $settingableType, ?string $settingableId, ?array $columns = null): ?SettingModel
    {
        $query = SettingModel::query()
            ->when($columns !== null, fn($query) => $query->select($columns))
            ->where('category', $s['category'])
            ->where('key', $s['key']);

        if ($settingableType === null) {
            $query->whereNull('settingable_type');
        } else {
            $query->where('settingable_type', $settingableType);
        }

        if ($settingableId === null) {
            $query->whereNull('settingable_id');
        } else {
            $query->where('settingable_id', $settingableId);
        }

        return $query->first();
    }

    private static function registerDefinition(array $s, string $source): string
    {
        $existing = SettingModel::query()
            ->where('category', $s['category'])
            ->where('key', $s['key'])
            ->get(['owner_package', 'definition_key']);

        if ($existing->contains(fn(SettingModel $setting): bool => !$setting->owner_package || !$setting->definition_key)) {
            throw new LogicException(sprintf(
                'Setting "%s.%s" has no complete ownership metadata and must be explicitly adopted before it can be changed.',
                $s['category'],
                $s['key'],
            ));
        }

        $foreignOwner = $existing->pluck('owner_package')->first(fn(?string $owner): bool => $owner !== $source);
        if ($foreignOwner !== null) {
            throw new LogicException(sprintf(
                'Setting path "%s.%s" is owned by "%s" and cannot be registered by "%s".',
                $s['category'],
                $s['key'],
                $foreignOwner,
                $source,
            ));
        }

        $definitionKeys = $existing->pluck('definition_key')->unique()->values();
        if ($definitionKeys->count() > 1) {
            throw new LogicException(sprintf('Setting "%s.%s" has multiple definition identifiers.', $s['category'], $s['key']));
        }

        SettingRegistry::register([
            [
                'category' => $s['category'],
                'key' => $s['key'],
                'definition_key' => $definitionKeys->first(),
            ],
        ], $source);

        return SettingRegistry::definitionKey($s['category'] . '.' . $s['key'])
            ?? throw new LogicException('Unable to resolve the registered setting definition key.');
    }

    private static function createDefinition(
        array $s,
        mixed $value,
        mixed $default,
        string $type,
        array $properties,
        ?string $settingableType,
        ?string $settingableId,
        string $source,
        ?string $definitionKey = null,
    ): SettingModel {
        $definitionKey ??= self::registerDefinition($s, $source);

        $data = [
            'category' => $s['category'],
            'key' => $s['key'],
            'owner_package' => $source,
            'definition_key' => $definitionKey,
            'value' => $value,
            'default_value' => $default,
            'type' => $type,
            'properties' => $properties,
        ];

        if ($settingableType !== null) {
            $data['settingable_type'] = $settingableType;
            $data['settingable_id'] = $settingableId;
        }

        return SettingModel::query()->create($data);
    }

    private static function assertSource(?string $source, string $operation): void
    {
        if (!$source) {
            throw new LogicException("Setting::{$operation}() requires the owning package source when creating a setting definition.");
        }
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
