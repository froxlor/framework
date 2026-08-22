<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Http\Requests\ComposerPackageRequest;
use Froxlor\Packages\Resources\PackageResource;
use Froxlor\Packages\Services\MarketplaceService;
use Froxlor\Packages\Services\PackageService;
use Froxlor\UI\Support\UI;

class PackageController extends Controller
{
    public function index()
    {
        return UI::render(PackageResource::class, 'index');
    }

    public function create()
    {
        return UI::render(PackageResource::class, 'create', [
            'package' => request()->string('package')->toString() ?: null,
        ]);
    }

    public function install(string $package, PackageService $packageService, MarketplaceService $marketplaceService)
    {
        $name = str_replace(':', '/', $package);
        $catalogEntry = collect($marketplaceService->catalog())->firstWhere('name', $name);

        if ($catalogEntry && $catalogEntry['requires_credentials']) {
            return back()->with('message', [
                'error',
                trans('froxlor-packages::generic.marketplace_credentials_required'),
            ]);
        }

        $response = $packageService->requirePackage($name);

        return back()->with('message', [$response['status'], $response['message']]);
    }

    public function edit(string $package)
    {
        return UI::render(PackageResource::class, 'edit', [
            'package' => $package
        ]);
    }

    public function upgrade(PackageService $packageService)
    {
        $response = $packageService->updatePackage();

        return back()->with('message', [$response['status'], $response['message']]);
    }

    public function uninstall(string $package, PackageService $packageService)
    {
        $dependants = $packageService->findDependant(str_replace(':', '/', $package));

        if ($dependants !== []) {
            return redirect()->route('packages.index')->with('message', [
                'error',
                trans('froxlor-packages::generic.package_has_dependants', [
                    'package' => str_replace(':', '/', $package),
                    'dependants' => implode(', ', array_keys($dependants)),
                ]),
            ]);
        }

        return UI::render(PackageResource::class, 'uninstall', [
            'package' => $package,
        ]);
    }
}
