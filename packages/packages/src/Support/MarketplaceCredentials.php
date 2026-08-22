<?php

namespace Froxlor\Packages\Support;

use Froxlor\Core\Support\Setting;

/**
 * Credentials used to authenticate against packages.froxlor.org — for now only to raise the
 * discovery repository's rate limits, but eventually to install paid marketplace packages.
 * Stored via the Setting store (editable from the UI) with the FROXLOR_PACKAGES_TOKEN env var
 * as the initial/deployment-time default.
 */
class MarketplaceCredentials
{
    private const DEFAULT_USERNAME = 'developers';

    public static function username(): string
    {
        return Setting::get('packages.marketplace_username') ?: self::DEFAULT_USERNAME;
    }

    public static function token(): ?string
    {
        return Setting::get('packages.marketplace_token') ?: config('packages.token');
    }

    public static function configured(): bool
    {
        return (bool)self::token();
    }

    public static function save(?string $username, ?string $token): void
    {
        Setting::set('packages.marketplace_username', $username ?: self::DEFAULT_USERNAME);
        Setting::set('packages.marketplace_token', $token);
    }
}
