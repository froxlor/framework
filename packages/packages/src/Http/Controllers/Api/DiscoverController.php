<?php

namespace Froxlor\Packages\Http\Controllers\Api;

use Froxlor\Core\Support\Response;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Support\Facades\Cache;

class DiscoverController extends Controller
{
    public function index(PackageService $packageService)
    {
        return Response::jsonResource(
            Cache::remember('packages.discover', 300, function () use ($packageService) {
                return $packageService->availablePackages();
            })
        );
    }
}
