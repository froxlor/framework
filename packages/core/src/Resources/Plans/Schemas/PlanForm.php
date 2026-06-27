<?php

namespace Froxlor\Core\Resources\Plans\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Local;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class PlanForm
{
    public static function schema(): array
    {
        return [
            Schemas\Components\Section::make('main')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->default('Plan ' . str()->random(4))
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(trans('froxlor-core::generic.description')),
                ])
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
