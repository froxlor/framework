<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Resources\Overview\OverviewResource;
use Froxlor\UI\Support\UI;

class PageController extends Controller
{
    public function index()
    {
        return UI::render(OverviewResource::class, 'index');
    }
}
