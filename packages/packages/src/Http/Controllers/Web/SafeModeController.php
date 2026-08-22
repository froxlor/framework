<?php

namespace Froxlor\Packages\Http\Controllers\Web;

use Froxlor\Packages\Http\Controllers\Controller;
use Froxlor\Packages\Support\SafeModeRegistry;

class SafeModeController extends Controller
{
    public function enable(string $package, SafeModeRegistry $safeModeRegistry)
    {
        $safeModeRegistry->enable(str_replace(':', '/', $package));

        return back()->with('message', ['success', trans('froxlor-packages::generic.package_enabled_successfully')]);
    }
}
