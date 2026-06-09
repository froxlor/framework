<?php

namespace Froxlor\Core\Resources\Plans\Relations\Tenants\Schemas;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class TenantView
{
    public static function schema(Tenant $tenant, Plan $plan): array
    {
        return [
            Schemas\Components\Tabs::make('plans.show.tabs')
                ->props(['plan' => $plan])
                ->components([
                    Schemas\Components\Tab::make('plans.show.tabs.details')
                        ->sort(0001)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            // info widgets o.Ä.?
                        ]),

                    Schemas\Components\Tab::make('plans.show.tabs.edit')
                        ->sort(0002)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('plan')
                                ->components([
                                    Schemas\Components\Section::make('section_a')
                                        ->title(trans('froxlor-core::generic.title'))
                                        ->components([
                                            Forms\Components\TextInput::make('name')
                                                ->label(trans('froxlor-core::generic.name'))
                                                ->required(),

                                            Forms\Components\TextInput::make('description')
                                                ->label(trans('froxlor-core::generic.title')),
                                        ]),
                                ]),
                        ]),

                    Schemas\Components\Tab::make('plans.show.tabs.resources')
                        ->sort(0100)
                        ->label(trans('froxlor-core::generic.resources'))
                        ->components([
                            Schemas\Components\Text::make('fixme')
                                ->label('How to get model-relation "resources" in here without separate API call?')
                        ]),
                ]),
        ];
    }

    public static function actions(Tenant $tenant, Plan $plan): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $tenant->name]))
                ->href(route('tenants.show', ['tenant' => $tenant]))
                ->icon('circle-chevron-left'),
        ];
    }
}
