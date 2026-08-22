<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Services\MarketplaceService;

class DiscoveryController extends Controller
{
    public function index(MarketplaceService $marketplaceService)
    {
        return view('froxlor-packages::discover.index', [
            'packages' => $marketplaceService->catalog(),
        ]);
    }
}
