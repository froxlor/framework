<?php

namespace Froxlor\UI\Http\Controllers\Api;

use Froxlor\Core\Support\Setting;
use Froxlor\UI\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Artisan;

class AppearanceController extends Controller
{
    public function index()
    {
        return JsonResource::make([
            'theme' => Setting::get('ui.theme'),
            'colors' => [
                'base' => Setting::get('ui.colors.base'),
                'dark' => Setting::get('ui.colors.dark'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'theme' => 'required|in:light,dark,system',
            'colors.base' => 'required|array',
            'colors.dark' => 'required|array',
        ]);

        Setting::set('ui.theme', $data['theme'], 'string');
        Setting::set('ui.colors.base', $data['colors']['base'], 'array');
        Setting::set('ui.colors.dark', $data['colors']['dark'], 'array');

        Artisan::call('view:clear');

        return response()->noContent();
    }
}
