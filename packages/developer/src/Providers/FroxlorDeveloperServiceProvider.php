<?php

namespace Froxlor\Developer\Providers;

use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\UI\Pushable\SettingLink;
use Froxlor\UI\Pushable\SidebarLink;
use Froxlor\UI\Support\UI;
use Illuminate\Support\Str;

class FroxlorDeveloperServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        // Only load in local environment and with debug mode enabled
        if (app()->isLocal() && app()->hasDebugModeEnabled()) {
            // Routes
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

            // Views
            $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-developer');

            // User Interface
            $this->extendUserInterface();
        }
    }

    public function register(): void
    {
        //
    }

    /**
     * Register navigation items and other UI related stuff.
     */
    private function extendUserInterface(): void
    {
        UI::push('settings', items: [
            SettingLink::make('developers')
                ->label('Developers')
                ->route(fn() => route('developers.index'))
                ->icon('code')
                ->badge('DEV', 'default'),
        ]);

        $layouts = $this->getNavigationItems('layouts', __DIR__ . '/../../resources/views/docs/layouts/*.blade.php');
        $navigations = $this->getNavigationItems('navigations', __DIR__ . '/../../resources/views/docs/navigations/*.blade.php');
        $components = $this->getNavigationItems('components', __DIR__ . '/../../resources/views/docs/components/*.blade.php');
        $forms = $this->getNavigationItems('forms', __DIR__ . '/../../resources/views/docs/forms/*.blade.php');
        $pages = $this->getNavigationItems('pages', __DIR__ . '/../../resources/views/docs/pages/*.blade.php');
        $tables = $this->getNavigationItems('tables', __DIR__ . '/../../resources/views/docs/tables/*.blade.php');
        $schema = $this->getNavigationItems('schema', __DIR__ . '/../../resources/views/docs/schema/*.blade.php');
        $utilities = $this->getNavigationItems('utilities', __DIR__ . '/../../resources/views/docs/utilities/*.blade.php');

        UI::push('developer', items: [
            SidebarLink::make('getting-started')
                ->label('Getting started')
                ->route(fn() => route('developers.index'))
                ->icon('book-open'),

            SidebarLink::make('layouts')
                ->label('Layouts')
                ->icon('panels-top-left'),

            ...$this->registerNavigationItems($layouts),

            SidebarLink::make('navigations')
                ->label('Navigations')
                ->icon('panel-left-dashed'),

            ...$this->registerNavigationItems($navigations),

            SidebarLink::make('components')
                ->label('Components')
                ->badge(count($components))
                ->icon('box'),

            ...$this->registerNavigationItems($components),

            SidebarLink::make('forms')
                ->label('Forms')
                ->icon('square-pen'),

            ...$this->registerNavigationItems($forms),

            SidebarLink::make('pages')
                ->label('Pages')
                ->icon('layout-dashboard'),

            ...$this->registerNavigationItems($pages),

            SidebarLink::make('tables')
                ->label('Tables')
                ->icon('table'),

            ...$this->registerNavigationItems($tables),

            SidebarLink::make('schema')
                ->label('Schema')
                ->icon('square-chart-gantt'),

            ...$this->registerNavigationItems($schema),

            SidebarLink::make('utilities')
                ->label('Utilities')
                ->icon('wrench'),

            ...$this->registerNavigationItems($utilities),
        ]);

    }

    private function getNavigationItems(string $key, string $path): array
    {
        $items = array_map(function ($file) use ($key) {
            $label = Str::title(str_replace(['-', '.blade', '.php'], ' ', pathinfo($file, PATHINFO_FILENAME)));
            $slug = Str::slug(strtolower($label));
            $content = file_get_contents($file);
            [$badge, $badgeVariant] = preg_match('/{{--\s*Status\s*:\s*([^,}\r\n]+?)(?:\s*,\s*([^}\r\n]+))?\s*--}}/is', $content, $m)
                ? [trim($m[1]), isset($m[2]) && $m[2] !== '' ? trim($m[2]) : 'outline']
                : [null, 'outline'];

            return [
                'key' => strtolower($key . '.' . $slug),
                'folder' => $key,
                'page' => $slug,
                'label' => $label,
                'badge' => $badge,
                'badgeVariant' => $badgeVariant,
            ];
        }, glob($path));

        return $items;
    }

    private function registerNavigationItems(array $items): array
    {
        $registry = [];

        foreach ($items as $item) {
            $registry[] = SidebarLink::make($item['key'])
                ->label($item['label'])
                ->route(fn() => route('developers.docs', [
                    'folder' => $item['folder'],
                    'page' => $item['page'],
                ]))
                ->badge($item['badge'], $item['badgeVariant']);
        }

        return $registry;
    }
}
