<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class SettingRegistry
{
    /**
     * @var array<string, array{category: string, key: string, definition_key: string, source: string}>
     */
    private static array $settings = [];

    /**
     * Register setting definitions exposed by a package.
     *
     * Setting paths are global definition identifiers. Resource-specific values still share
     * the same definition, so a package cannot shadow a setting by registering it for another
     * resource scope.
     *
     * @param array<int, array{path?: string, category?: string, key?: string, definition_key?: string}> $settings
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function register(array $settings, string $source): void
    {
        self::assertSource($source);

        foreach ($settings as $setting) {
            self::registerDefinition($setting, $source);
        }
    }

    /**
     * Return all registered definitions sorted by path.
     *
     * @return array<int, array{category: string, key: string, definition_key: string, source: string}>
     */
    public static function all(): array
    {
        $settings = array_values(self::$settings);

        usort($settings, fn(array $left, array $right) => [$left['category'], $left['key']] <=> [$right['category'], $right['key']]);

        return $settings;
    }

    /**
     * Return the stable definition key for a registered path.
     */
    public static function definitionKey(string $path): ?string
    {
        $parsed = self::parsePath($path);
        $registered = self::$settings[self::registryKey($parsed)] ?? null;

        return $registered['definition_key'] ?? null;
    }

    /**
     * Rename a setting definition owned by the given package.
     *
     * All global, type-specific and instance-specific values belonging to the definition are
     * moved together. Direct Eloquent updates of category/key remain blocked by the Setting
     * model, so this is the single supported metadata mutation path.
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function rename(string $path, string $newPath, string $source): void
    {
        self::assertSource($source);

        $old = self::parsePath($path);
        $new = self::parsePath($newPath);

        if ($old === $new) {
            return;
        }

        DB::transaction(function () use ($old, $new, $path, $newPath, $source): void {
            $oldRows = Setting::query()
                ->where('category', $old['category'])
                ->where('key', $old['key'])
                ->get(['id', 'owner_package', 'definition_key']);

            if ($oldRows->isEmpty()) {
                throw new LogicException(sprintf('Setting "%s" does not exist and cannot be renamed.', $path));
            }

            $definitionKeys = $oldRows->pluck('definition_key')->filter()->unique()->values();
            $owners = $oldRows->pluck('owner_package')->unique()->values();

            if ($owners->contains(null) || $definitionKeys->count() !== 1 || $owners->count() !== 1) {
                throw new LogicException(sprintf('Setting "%s" has incomplete ownership metadata and cannot be renamed.', $path));
            }

            $owner = $owners->first();
            if ($owner !== $source) {
                throw new LogicException(sprintf(
                    'Setting "%s" is owned by "%s" and cannot be renamed by "%s".',
                    $path,
                    $owner,
                    $source,
                ));
            }

            $newRows = Setting::query()
                ->where('category', $new['category'])
                ->where('key', $new['key'])
                ->whereNotIn('id', $oldRows->pluck('id'))
                ->exists();

            if ($newRows || isset(self::$settings[self::registryKey($new)])) {
                throw new LogicException(sprintf('Setting path "%s" is already registered.', $newPath));
            }

            $definitionKey = $definitionKeys->first();

            Setting::query()
                ->whereIn('id', $oldRows->pluck('id'))
                ->update([
                    'category' => $new['category'],
                    'key' => $new['key'],
                ]);

            unset(self::$settings[self::registryKey($old)]);
            self::$settings[self::registryKey($new)] = [
                'category' => $new['category'],
                'key' => $new['key'],
                'definition_key' => $definitionKey,
                'source' => $source,
            ];
        });
    }

    /**
     * Adopt legacy rows whose original package could not be inferred during the ownership
     * migration. Package migrations should call this explicitly before registering the setting.
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function adopt(string $path, string $source): void
    {
        self::assertSource($source);
        $parsed = self::parsePath($path);
        $rows = Setting::query()
            ->where('category', $parsed['category'])
            ->where('key', $parsed['key'])
            ->get(['id', 'owner_package', 'definition_key']);

        if ($rows->isEmpty()) {
            throw new LogicException(sprintf('Setting "%s" does not exist and cannot be adopted.', $path));
        }

        $foreignOwner = $rows->pluck('owner_package')->first(
            fn(?string $owner): bool => $owner !== null && $owner !== $source,
        );
        if ($foreignOwner !== null) {
            throw new LogicException(sprintf(
                'Setting "%s" is already owned by "%s" and cannot be adopted by "%s".',
                $path,
                $foreignOwner,
                $source,
            ));
        }

        $definitionKeys = $rows->pluck('definition_key')->filter()->unique()->values();
        if ($definitionKeys->count() > 1) {
            throw new LogicException(sprintf('Setting "%s" has multiple definition identifiers.', $path));
        }

        $definitionKey = $definitionKeys->first() ?? (string)Str::ulid();

        self::register([
            [
                'category' => $parsed['category'],
                'key' => $parsed['key'],
                'definition_key' => $definitionKey,
            ],
        ], $source);

        DB::transaction(function () use ($rows, $source, $definitionKey): void {
            Setting::query()
                ->whereIn('id', $rows->pluck('id'))
                ->update([
                    'owner_package' => $source,
                    'definition_key' => $definitionKey,
                ]);
        });
    }

    /**
     * Guard used by the Setting model for direct definition metadata updates.
     */
    public static function assertDefinitionIsImmutable(Setting $setting): void
    {
        throw new LogicException(sprintf(
            'Setting definition "%s.%s" is immutable; use SettingRegistry::rename() owned by "%s".',
            $setting->category,
            $setting->key,
            $setting->owner_package ?? 'its package',
        ));
    }

    /**
     * Register one definition and return its stable key.
     *
     * @param array{path?: string, category?: string, key?: string, definition_key?: string} $setting
     */
    private static function registerDefinition(array $setting, string $source): string
    {
        $parsed = self::parseDefinition($setting);
        $registryKey = self::registryKey($parsed);
        $definitionKey = $setting['definition_key'] ?? self::$settings[$registryKey]['definition_key'] ?? (string)Str::ulid();
        $registered = self::$settings[$registryKey] ?? null;

        if ($registered !== null && ($registered['source'] !== $source || $registered['definition_key'] !== $definitionKey)) {
            throw new LogicException(sprintf(
                'Setting path "%s.%s" is already registered by "%s" and cannot be registered by "%s".',
                $parsed['category'],
                $parsed['key'],
                $registered['source'],
                $source,
            ));
        }

        self::$settings[$registryKey] = [
            'category' => $parsed['category'],
            'key' => $parsed['key'],
            'definition_key' => $definitionKey,
            'source' => $source,
        ];

        return $definitionKey;
    }

    /**
     * @return array{category: string, key: string}
     */
    private static function parseDefinition(array $setting): array
    {
        if (isset($setting['path'])) {
            return self::parsePath($setting['path']);
        }

        if (empty($setting['category']) || !is_string($setting['category']) || empty($setting['key']) || !is_string($setting['key'])) {
            throw new InvalidArgumentException('Registered settings require a non-empty category/key or path.');
        }

        return [
            'category' => $setting['category'],
            'key' => $setting['key'],
        ];
    }

    /**
     * @return array{category: string, key: string}
     */
    private static function parsePath(string $path): array
    {
        $parts = explode('.', $path, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException("Invalid settings path: {$path}");
        }

        return [
            'category' => $parts[0],
            'key' => $parts[1],
        ];
    }

    /**
     * @param array{category: string, key: string} $parsed
     */
    private static function registryKey(array $parsed): string
    {
        return $parsed['category'] . '.' . $parsed['key'];
    }

    private static function assertSource(string $source): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('Setting definitions require a non-empty package source.');
        }
    }
}
