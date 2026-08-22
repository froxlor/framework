<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Resources\MarketplaceCredentialsResource;
use Froxlor\UI\Support\UI;

class MarketplaceCredentialsController extends Controller
{
    public function edit()
    {
        return UI::render(MarketplaceCredentialsResource::class, 'edit');
    }
}
