<?php

namespace Froxlor\Packages\Http\Controllers\Api;

use Froxlor\Core\Support\Response;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Http\Requests\ComposerPackageRequest;
use Froxlor\Packages\Services\PackageService;

class PackageController extends Controller
{
    public function index(PackageService $packageService)
    {
        return Response::jsonResource($packageService->packages());
    }

    public function show(string $package, PackageService $packageService)
    {
        foreach ($packageService->packages() as $pkg) {
            if ($pkg['id'] === $package) {
                return Response::jsonResource($pkg);
            }
        }

        return response()->noContent();
    }

    public function store(ComposerPackageRequest $request, PackageService $packageService)
    {
        $response = $packageService->requirePackage($request->package);

        if ($response['status'] !== 'success') {
            return response()->json(['errors' => ['package' => $response['message']]], 409);
        }

        return response()->noContent();
    }

    public function update(ComposerPackageRequest $request, PackageService $packageService)
    {
        $response = $packageService->updatePackage($request->package);

        if ($response['status'] !== 'success') {
            return response()->json(['errors' => ['package' => $response['message']]], 409);
        }

        return response()->noContent();
    }

    public function destroy(ComposerPackageRequest $request, PackageService $packageService)
    {
        $response = $packageService->removePackage($request->package);

        if ($response['status'] !== 'success') {
            return response()->json(['errors' => ['package' => $response['message']]], 409);
        }

        return response()->noContent();
    }
}
