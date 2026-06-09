<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Resources\PackageResource;
use Froxlor\UI\Support\UI;

class UpdaterController extends Controller
{
    public function index()
    {
        return UI::render(PackageResource::class, 'updater');
    }
}
