<?php

namespace Froxlor\Packages\Http\Middleware;

use Closure;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Http\Request;

/**
 * Hard lockout: while any package has a pending update completion (see
 * PackageServiceProvider::requireCompletion()), every web request is redirected to that
 * package's completion route until the admin answers it — with a small escape hatch so they can
 * still reach the Packages page and disable the offending package instead.
 */
class EnsurePackageUpdatesAreComplete
{
    /**
     * Route names that stay reachable even while a completion is pending.
     */
    private const ALLOWED_ROUTES = [
        'packages.index',
        'packages.edit',
        'packages.enable',
        'packages.disable',
        'packages.complete',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $pending = app(PackageService::class)->pendingCompletions();

        if ($pending === []) {
            return $next($request);
        }

        $allowedRoutes = [...self::ALLOWED_ROUTES, ...array_column($pending, 'route')];

        if ($request->routeIs(...$allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route(reset($pending)['route']);
    }
}
