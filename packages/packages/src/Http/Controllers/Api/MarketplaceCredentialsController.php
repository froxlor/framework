<?php

namespace Froxlor\Packages\Http\Controllers\Api;

use Froxlor\Core\Support\Response;
use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Http\Requests\UpdateMarketplaceCredentialsRequest;
use Froxlor\Packages\Support\MarketplaceCredentials;

class MarketplaceCredentialsController extends Controller
{
    public function show()
    {
        return Response::jsonResource([
            'username' => MarketplaceCredentials::username(),
            'configured' => MarketplaceCredentials::configured(),
        ]);
    }

    public function update(UpdateMarketplaceCredentialsRequest $request)
    {
        $data = $request->validated();

        MarketplaceCredentials::save($data['username'] ?? null, $data['token']);

        return response()->noContent();
    }
}
