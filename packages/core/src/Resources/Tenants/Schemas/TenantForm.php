<?php

namespace Froxlor\Core\Resources\Tenants\Schemas;

use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class TenantForm
{
    public static function schema(): array
    {
        return [
            Schemas\Components\Section::make('section_a')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(trans('froxlor-core::generic.title')),
                ]),

            Schemas\Components\Section::make('section_b')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\TextInput::make('parent_tenant_id')
                        ->label('Parent tenant ID')
                        ->required(),

                    Forms\Components\TextInput::make('plan_id')
                        ->label('Plan ID')
                        ->required(),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('resources.tenants.index')),
        ];
    }
}
