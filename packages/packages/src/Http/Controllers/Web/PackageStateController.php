<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Exception;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Services\PackageService;

class PackageStateController extends Controller
{
    public function enable(string $package, PackageService $packageService)
    {
        try {
            $packageService->enablePackage(str_replace(':', '/', $package));
        } catch (Exception $e) {
            return back()->with('message', ['error', $e->getMessage()]);
        }

        return back()->with('message', ['success', trans('froxlor-packages::generic.package_enabled_successfully')]);
    }

    public function disable(string $package, PackageService $packageService)
    {
        try {
            $packageService->disablePackage(str_replace(':', '/', $package));
        } catch (Exception $e) {
            return back()->with('message', ['error', $e->getMessage()]);
        }

        return back()->with('message', ['success', trans('froxlor-packages::generic.package_disabled_successfully')]);
    }

    /**
     * Indirection so the Packages table can link to a "complete this package's pending update"
     * action via a fixed route name (as intendedRoute() requires) even though the actual
     * destination route name varies per package.
     */
    public function complete(string $package, PackageService $packageService)
    {
        $provider = $packageService->findProvider(str_replace(':', '/', $package));
        $pending = $provider?->pendingCompletion();

        if (!$pending) {
            return redirect()->route('packages.index');
        }

        return redirect()->route($pending['route']);
    }
}
