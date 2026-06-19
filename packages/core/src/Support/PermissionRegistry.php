<?php

namespace Froxlor\Core\Support;

use FilesystemIterator;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Services\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class PermissionRegistry
{
    /**
     * @var array<string, array{key: string, name: string, description?: string|null, source: string}>
     */
    private static array $permissions = [];

    /**
     * Register permissions exposed by a package or application component.
     *
     * Package maintainers can call this from their service provider with plain arrays.
     * Permission keys are globally unique security identifiers, so conflicts are rejected
     * immediately instead of letting the database fail later during seeding.
     *
     * @param array<int, array{key: string, name: string, description?: string|null}> $permissions
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function register(array $permissions, string $source): void
    {
        foreach ($permissions as $permission) {
            self::registerPermission($permission, $source);
        }
    }

    /**
     * Register permissions returned by a model's `getAllPermissions()` method.
     *
     * This keeps existing model-defined permissions reusable for Core and external
     * packages while still routing all keys through the central conflict check.
     *
     * @param class-string<Model> $modelClass
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function registerModel(string $modelClass, ?string $source = null): void
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException('Permission model does not exist: ' . $modelClass);
        }

        if (!is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException('Permission model must be an Eloquent model: ' . $modelClass);
        }

        if (!in_array(HasPermissions::class, class_uses_recursive($modelClass), true)) {
            return;
        }

        self::register($modelClass::getAllPermissions(), $source ?? $modelClass);
    }

    /**
     * Register all permission-aware models in a package model directory.
     *
     * The directory scan is intentionally shallow because package model classes are the
     * public registration unit. Packages with custom layouts can call `registerModel()`
     * or `register()` directly.
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function registerModelsFrom(string $directory, string $namespace, ?string $source = null): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new FilesystemIterator($directory) as $fileInfo) {
            if ($fileInfo->isDir() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            self::registerModel(
                rtrim($namespace, '\\') . '\\' . $fileInfo->getBasename('.php'),
                $source ?? $namespace,
            );
        }
    }

    /**
     * Register permission-aware models from every package using the standard layout.
     *
     * Packages are discovered through their `composer.json` PSR-4 autoload definition.
     * For every namespace that points to a `src/` directory, the corresponding
     * `src/Models` directory is scanned for models using `HasPermissions`. This keeps
     * the common extension path simple: package authors add `HasPermissions` to their
     * model and Core takes care of the permission synchronization.
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function registerPackageModelsFrom(string $packagesDirectory): void
    {
        if (!is_dir($packagesDirectory)) {
            return;
        }

        foreach (new FilesystemIterator($packagesDirectory) as $packageDirectory) {
            if (!$packageDirectory->isDir()) {
                continue;
            }

            $composerFile = $packageDirectory->getPathname() . '/composer.json';
            if (!is_file($composerFile)) {
                continue;
            }

            self::registerPackageModels($composerFile);
        }
    }

    /**
     * Return all registered permissions sorted by key.
     *
     * @return array<int, array{key: string, name: string, description?: string|null, source: string}>
     */
    public static function all(): array
    {
        $permissions = array_values(self::$permissions);

        usort($permissions, fn(array $left, array $right) => $left['key'] <=> $right['key']);

        return $permissions;
    }

    /**
     * Synchronize registered permissions into the database.
     *
     * Existing permissions keep their ULIDs and role assignments. Only user-facing
     * metadata owned by the registry is updated.
     */
    public static function sync(): void
    {
        foreach (self::all() as $permission) {
            Permission::query()->updateOrCreate(
                ['key' => $permission['key']],
                [
                    'name' => $permission['name'],
                    'description' => $permission['description'] ?? null,
                ],
            );
        }
    }

    /**
     * Register permission-aware models for one package composer file.
     */
    private static function registerPackageModels(string $composerFile): void
    {
        $composer = json_decode((string)file_get_contents($composerFile), true);
        if (!is_array($composer)) {
            throw new InvalidArgumentException('Unable to read package composer metadata: ' . $composerFile);
        }

        $packageRoot = dirname($composerFile);
        $packageName = $composer['name'] ?? basename($packageRoot);
        $autoload = $composer['autoload']['psr-4'] ?? [];

        foreach ($autoload as $namespace => $path) {
            foreach ((array)$path as $autoloadPath) {
                $normalizedPath = trim((string)$autoloadPath, '/');

                if (!Str::endsWith($normalizedPath, 'src')) {
                    continue;
                }

                self::registerModelsFrom(
                    $packageRoot . '/' . $normalizedPath . '/Models',
                    rtrim((string)$namespace, '\\') . '\\Models',
                    (string)$packageName,
                );
            }
        }
    }

    /**
     * Register one validated permission definition.
     *
     * @param array{key?: string, name?: string, description?: string|null} $permission
     */
    private static function registerPermission(array $permission, string $source): void
    {
        if (empty($permission['key']) || !is_string($permission['key'])) {
            throw new InvalidArgumentException('Registered permissions require a non-empty string key.');
        }

        if (empty($permission['name']) || !is_string($permission['name'])) {
            throw new InvalidArgumentException('Registered permission "' . $permission['key'] . '" requires a non-empty string name.');
        }

        $key = $permission['key'];
        $registered = self::$permissions[$key] ?? null;

        if ($registered !== null && ($registered['source'] !== $source || $registered['name'] !== $permission['name'])) {
            throw new LogicException(sprintf(
                'Permission key "%s" is already registered by "%s" and cannot be registered by "%s".',
                $key,
                $registered['source'],
                $source,
            ));
        }

        self::$permissions[$key] = [
            'key' => $key,
            'name' => $permission['name'],
            'description' => $permission['description'] ?? null,
            'source' => $source,
        ];
    }
}
