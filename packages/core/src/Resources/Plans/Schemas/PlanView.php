<?php

namespace Froxlor\Core\Resources\Plans\Schemas;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Resources\Plans\Relations\Resource\Schemas\ResourceTable;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class PlanView
{
    public static function schema(Plan $plan): array
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
                            Schemas\Components\Relation::make('resources')
                                ->fetch(route('api.plans.resources.index', $plan))
                                //  ->intendedRoute('plans.resources.show', ['plan' => $plan->id, 'resource' => '{id}'])
                                ->columns(ResourceTable::columns($plan))
                                ->actions(ResourceTable::actions($plan)),
                        ]),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('resources.plans.index')),
        ];
    }
}
