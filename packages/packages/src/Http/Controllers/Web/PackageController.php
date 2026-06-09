<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Http\Requests\ComposerPackageRequest;
use Froxlor\Packages\Resources\PackageResource;
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

    public function install(string $package, PackageService $packageService)
    {
        $response = $packageService->requirePackage(str_replace(':', '/', $package));

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

    public function uninstall(string $package)
    {
        return UI::render(PackageResource::class, 'uninstall', [
            'package' => $package,
        ]);
    }
}
