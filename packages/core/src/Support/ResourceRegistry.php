<?php

namespace Froxlor\Core\Support;

use FilesystemIterator;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Services\Traits\IsEnvironmentResource;
use Froxlor\Core\Services\Traits\IsResource;
use Froxlor\Core\Services\Traits\IsTenantResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class ResourceRegistry
{
    /**
     * @var array<string, array{key: string, name: string, description?: string|null, model_type: class-string<Model>, type: string, source: string}>
     */
    private static array $resources = [];

    /**
     * Register resource definitions exposed by a package or application component.
     *
     * Resource keys are unique per scope. The same key may exist in tenant and
     * environment scope, but a package must not register two different model classes
     * for the same key and scope because plan limits would become ambiguous.
     *
     * @param array<int, array{key: string, name: string, model_type: class-string<Model>, type: string, description?: string|null}> $resources
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function register(array $resources, string $source): void
    {
        foreach ($resources as $resource) {
            self::registerResource($resource, $source);
        }
    }

    /**
     * Register resource definitions declared by an Eloquent model.
     *
     * Models must use `IsResource` plus at least one scope marker trait
     * (`IsTenantResource` or `IsEnvironmentResource`). This keeps internal models that
     * use the base resource contract from becoming plan resources accidentally.
     *
     * @param class-string<Model> $modelClass
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function registerModel(string $modelClass, ?string $source = null): void
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException('Resource model does not exist: ' . $modelClass);
        }

        if (!is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException('Resource model must be an Eloquent model: ' . $modelClass);
        }

        $traits = class_uses_recursive($modelClass);
        if (!in_array(IsResource::class, $traits, true)) {
            return;
        }

        $types = [];
        if (in_array(IsTenantResource::class, $traits, true)) {
            $types[] = 'tenant';
        }
        if (in_array(IsEnvironmentResource::class, $traits, true)) {
            $types[] = 'environment';
        }
        if ($types === []) {
            return;
        }

        $resources = [];
        foreach ($types as $type) {
            $resources[] = [
                'key' => $modelClass::getResourceKey(),
                'name' => self::defaultName($modelClass, $type, count($types) > 1),
                'model_type' => $modelClass,
                'type' => $type,
            ];
        }

        self::register($resources, $source ?? $modelClass);
    }

    /**
     * Register resource-aware models in a package model directory.
     *
     * The scan mirrors `PermissionRegistry`: standard packages only need models under
     * `src/Models`; packages with custom layouts can call `registerModel()` manually.
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
     * Register resource-aware models from every package using the standard layout.
     *
     * Packages are discovered through their `composer.json` PSR-4 autoload metadata.
     * Only models with `IsResource` and at least one scope marker are registered.
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
     * Return all registered resources sorted by scope and key.
     *
     * @return array<int, array{key: string, name: string, description?: string|null, model_type: class-string<Model>, type: string, source: string}>
     */
    public static function all(): array
    {
        $resources = array_values(self::$resources);

        usort($resources, fn(array $left, array $right) => [$left['type'], $left['key']] <=> [$right['type'], $right['key']]);

        return $resources;
    }

    /**
     * Synchronize registered resource definitions into the database.
     *
     * Existing resources keep their ULIDs and plan assignments. Registry sync only
     * updates metadata owned by package/resource definitions.
     */
    public static function sync(): void
    {
        foreach (self::all() as $resource) {
            Resource::query()->updateOrCreate(
                [
                    'key' => $resource['key'],
                    'model_type' => $resource['model_type'],
                    'type' => $resource['type'],
                ],
                [
                    'name' => $resource['name'],
                    'description' => $resource['description'] ?? null,
                ],
            );
        }
    }

    /**
     * Register resource-aware models for one package composer file.
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
     * Register one validated resource definition.
     *
     * @param array{key?: string, name?: string, model_type?: class-string<Model>, type?: string, description?: string|null} $resource
     */
    private static function registerResource(array $resource, string $source): void
    {
        if (empty($resource['key']) || !is_string($resource['key'])) {
            throw new InvalidArgumentException('Registered resources require a non-empty string key.');
        }

        if (empty($resource['name']) || !is_string($resource['name'])) {
            throw new InvalidArgumentException('Registered resource "' . $resource['key'] . '" requires a non-empty string name.');
        }

        if (empty($resource['model_type']) || !is_string($resource['model_type']) || !is_a($resource['model_type'], Model::class, true)) {
            throw new InvalidArgumentException('Registered resource "' . $resource['key'] . '" requires an Eloquent model type.');
        }

        if (!in_array($resource['type'] ?? null, ['tenant', 'environment'], true)) {
            throw new InvalidArgumentException('Registered resource "' . $resource['key'] . '" requires a valid resource type.');
        }

        $registryKey = $resource['type'] . ':' . $resource['key'];
        $registered = self::$resources[$registryKey] ?? null;

        if ($registered !== null && ($registered['source'] !== $source || $registered['model_type'] !== $resource['model_type'])) {
            throw new LogicException(sprintf(
                'Resource key "%s" for type "%s" is already registered by "%s" and cannot be registered by "%s".',
                $resource['key'],
                $resource['type'],
                $registered['source'],
                $source,
            ));
        }

        self::$resources[$registryKey] = [
            'key' => $resource['key'],
            'name' => $resource['name'],
            'description' => $resource['description'] ?? null,
            'model_type' => $resource['model_type'],
            'type' => $resource['type'],
            'source' => $source,
        ];
    }

    /**
     * Build a human-readable default name for model-based resource definitions.
     *
     * User resources exist in both scopes, so the scope prefix avoids ambiguous plan
     * editing labels while keeping package defaults simple.
     *
     * @param class-string<Model> $modelClass
     */
    private static function defaultName(string $modelClass, string $type, bool $includeScope): string
    {
        $key = str_replace('.', ' ', $modelClass::getResourceKey());

        return Str::headline($includeScope ? $type . ' ' . $key : $key);
    }
}
