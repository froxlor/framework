<?php

namespace Froxlor\Packages\Support;

use Illuminate\Foundation\PackageManifest;

/**
 * Extends Laravel's package discovery manifest so that packages soft-disabled in the
 * SafeModeRegistry are treated the same as composer.json's static extra.laravel.dont-discover
 * list — their service providers are simply never registered.
 */
class SafeModePackageManifest extends PackageManifest
{
    protected function packagesToIgnore()
    {
        return array_values(array_unique([
            ...parent::packagesToIgnore(),
            ...array_keys(app(SafeModeRegistry::class)->disabled()),
        ]));
    }
}
