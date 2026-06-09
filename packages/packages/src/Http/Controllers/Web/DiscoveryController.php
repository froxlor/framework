<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Resources\DiscoveryResource;
use Froxlor\UI\Support\UI;

class DiscoveryController extends Controller
{
    public function index()
    {
        return UI::render(DiscoveryResource::class, 'index');
    }
}
