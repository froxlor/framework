<?php

namespace Froxlor\Packages\Support;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Wired into bootstrap/app.php's withExceptions() as a renderable callback. Laravel's HTTP
 * kernel already wraps provider registration/boot in a try/catch and routes whatever it throws
 * through the exception handler, so a package crashing during boot lands here just like any
 * other exception — no need to touch the front controller.
 */
class SafeModeRecovery
{
    /**
     * One-shot query marker so a package that keeps crashing after being disabled (e.g. the
     * crash wasn't actually caused by that package) fails normally instead of redirect-looping.
     */
    private const RECOVERY_MARKER = 'froxlor_safe_mode_recovered';

    public static function handle(Throwable $e, Request $request): ?RedirectResponse
    {
        if ($request->boolean(self::RECOVERY_MARKER)) {
            return null;
        }

        $packageService = app(PackageService::class);
        $package = $packageService->findPackageForPath($e->getFile());

        // Non-toggleable packages (froxlor/framework) can never be disabled, so redirecting
        // would just replay the same exception — let it render normally instead.
        if (!$package || !$packageService->isToggleable($package)) {
            return null;
        }

        app(SafeModeRegistry::class)->disable($package, $e->getMessage(), auto: true);

        return redirect($request->fullUrlWithQuery([self::RECOVERY_MARKER => 1]));
    }
}
