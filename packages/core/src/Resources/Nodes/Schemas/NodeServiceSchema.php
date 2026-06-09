<?php

namespace Froxlor\Core\Resources\Nodes\Schemas;

use Froxlor\UI\Schemas;

class NodeServiceSchema
{
    public static function detailsTab(string $key, string $title, array $components): Schemas\Components\Tabs
    {
        return Schemas\Components\Tabs::make($key)
            ->components([
                Schemas\Components\Tab::make('details')
                    ->sort(1)
                    ->label(trans('froxlor-core::generic.details'))
                    ->components([
                        Schemas\Components\Section::make($key . '.meta')
                            ->title($title)
                            ->components($components),
                    ]),
            ]);
    }

    public static function standardStatusFields(object $service): array
    {
        return [
            Schemas\Components\Text::make('status')
                ->label(trans('froxlor-core::generic.status'))
                ->default(fn() => $service->status),
            Schemas\Components\Text::make('installed_at')
                ->label(trans('froxlor-core::generic.installed_at'))
                ->default(fn() => $service->installed_at?->format('Y-m-d H:i:s') ?: '-'),
            Schemas\Components\Text::make('configured_at')
                ->label(trans('froxlor-core::generic.configured_at'))
                ->default(fn() => $service->configured_at?->format('Y-m-d H:i:s') ?: '-'),
            Schemas\Components\Text::make('last_checked_at')
                ->label(trans('froxlor-core::generic.last_checked_at'))
                ->default(fn() => $service->last_checked_at?->format('Y-m-d H:i:s') ?: '-'),
            Schemas\Components\Text::make('is_reachable')
                ->label(trans('froxlor-core::generic.reachable'))
                ->default(fn() => $service->is_reachable ? trans('froxlor-core::generic.yes') : trans('froxlor-core::generic.no')),
            Schemas\Components\Text::make('last_error')
                ->label(trans('froxlor-core::generic.last_error'))
                ->default(fn() => $service->last_error ?: '-'),
        ];
    }
}
