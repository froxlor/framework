<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Setting;
use Froxlor\Core\Resources\Settings\SettingResource;
use Froxlor\UI\Pushable\SettingLink;
use Froxlor\UI\Support\UI;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index(?string $resource = null, ?string $resource_id = null)
    {
        if (request()->filled('category')) {
            return UI::render(SettingResource::class, 'index', [
                'resource' => $resource,
                'resource_id' => $resource_id,
            ]);
        }

        $categoryItems = Setting::query()
            ->whereNull('settingable_type')
            ->where(fn ($q) => $q->whereNull('properties->visible')->orWhere('properties->visible', true))
            ->selectRaw('category, COUNT(*) as settings_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(function (Setting $setting) {
                $category = (string) $setting->category;

                return SettingLink::make('settings.category.' . $category)
                    ->label(Str::of($category)->replace(['.', '_', '-'], ' ')->headline()->toString())
                    ->route(route('settings.index', ['category' => $category]))
                    ->icon($this->iconForCategory($category));
            });

        return view('froxlor-core::settings.index', [
            'items' => collect(array_merge(
                UI::stack('settings'),
                $categoryItems->map(fn (SettingLink $item) => $item->toObject())->all(),
            ))->sortBy('label')->values(),
        ]);
    }

    private function iconForCategory(string $category): string
    {
        return match (Str::lower($category)) {
            'api' => 'blocks',
            'auditlog', 'audit-log' => 'file-clock',
            'mail', 'smtp' => 'mail',
            'security', 'auth', 'authentication' => 'shield',
            'system', 'general' => 'settings',
            default => 'sliders-horizontal',
        };
    }
}
