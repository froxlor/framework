<?php

namespace Froxlor\Core\Support;

use Composer\InstalledVersions;

/**
 * Central access point for froxlor product release metadata.
 *
 * The froxlor release version describes the installed product/distribution and
 * is intentionally separate from Composer package versions. Packages can move
 * independently within a release series, while external product update checks
 * need one stable release value for the running froxlor installation.
 */
final class FroxlorVersion
{
    /**
     * Return the configured froxlor product release version.
     *
     * Release builds should provide this value through `FROXLOR_RELEASE_VERSION`.
     * Development installs fall back to Composer metadata for `froxlor/froxlor`
     * and finally to a valid development version string.
     */
    public static function release(): string
    {
        return (string) config('froxlor.release_version', self::installedApplicationVersion());
    }

    /**
     * Return the major/minor release series for compatibility checks.
     *
     * For a release like `3.0.7`, `3.0-rc1`, or `v3.0-dev1`, this returns
     * `3.0`. If the version does not start with a numeric major/minor pair,
     * the normalized release value is returned unchanged.
     */
    public static function releaseSeries(): string
    {
        $version = self::normalize(self::release());

        if (preg_match('/^(\d+\.\d+)/', $version, $matches)) {
            return $matches[1];
        }

        return $version;
    }

    /**
     * Build the default HTTP user agent for froxlor product services.
     *
     * This is used for product-level endpoints such as the external froxlor
     * release check and should not be confused with Composer package versions.
     */
    public static function userAgent(): string
    {
        return 'Froxlor/' . self::release();
    }

    /**
     * Resolve the installed application package version from Composer metadata.
     *
     * Composer may return branch aliases such as `dev-main` in development
     * workspaces. The hard fallback must stay compatible with froxlor's version
     * validation rules.
     */
    private static function installedApplicationVersion(): string
    {
        return InstalledVersions::getPrettyVersion('froxlor/froxlor')
            ?? InstalledVersions::getVersion('froxlor/froxlor')
            ?? '3.0-dev1';
    }

    /**
     * Remove an optional leading `v` prefix from a version string.
     */
    private static function normalize(string $version): string
    {
        return ltrim($version, 'v');
    }
}
