<?php

namespace Froxlor\Core\Support;

/**
 * Resolves which installed froxlor package (as tagged by extra.froxlor.type === "package" in
 * vendor/composer/installed.json) owns a given class or file path. Shared by the package
 * lifecycle contract (PackageServiceProvider::packageName()) and the Packages module's safe
 * mode crash recovery, so both agree on exactly the same notion of "package".
 */
class ComposerPackage
{
    /**
     * @var array<class-string, string|null>
     */
    private static array $classCache = [];

    public static function forClass(string $class): ?string
    {
        if (array_key_exists($class, self::$classCache)) {
            return self::$classCache[$class];
        }

        try {
            $path = (new \ReflectionClass($class))->getFileName();
        } catch (\ReflectionException) {
            $path = false;
        }

        return self::$classCache[$class] = $path ? self::forPath($path) : null;
    }

    public static function forPath(string $path): ?string
    {
        $installedPath = base_path('vendor/composer/installed.json');

        if (!file_exists($installedPath)) {
            return null;
        }

        $installed = json_decode(file_get_contents($installedPath), true);
        $packages = $installed['packages'] ?? $installed ?? [];
        $realPath = realpath($path) ?: $path;

        foreach ($packages as $package) {
            if (($package['extra']['froxlor']['type'] ?? null) !== 'package') {
                continue;
            }

            $installPath = $package['install-path'] ?? null;

            if (!$installPath) {
                continue;
            }

            $packagePath = realpath(base_path('vendor/composer/' . $installPath));

            if ($packagePath && str_starts_with($realPath, $packagePath . DIRECTORY_SEPARATOR)) {
                return $package['name'];
            }
        }

        return null;
    }
}
