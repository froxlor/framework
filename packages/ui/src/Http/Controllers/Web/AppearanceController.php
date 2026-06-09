<?php

namespace Froxlor\UI\Http\Controllers\Web;

use Froxlor\UI\Http\Controllers\Controller;
use Froxlor\UI\Resources\AppearanceResource;
use Froxlor\UI\Support\UI;

class AppearanceController extends Controller
{
    public function index()
    {
        return UI::render(AppearanceResource::class, 'index');
    }
}
