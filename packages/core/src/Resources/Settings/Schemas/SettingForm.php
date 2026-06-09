<?php

namespace Froxlor\Core\Resources\Settings\Schemas;

use Froxlor\UI\Schemas;

class SettingForm
{
    public static function schema(): array
    {
        return [
            Schemas\Components\Section::make('main')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\TextInput::make('category')
                        ->label(trans('froxlor-core::settings.category'))
                        ->required(),

                    Forms\Components\TextInput::make('key')
                        ->label(trans('froxlor-core::settings.key'))
                        ->required(),

                    Forms\Components\TextArea::make('value')
                        ->label(trans('froxlor-core::settings.value')),

                    Forms\Components\TextInput::make('default_value')
                        ->label(trans('froxlor-core::settings.default_value')),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('settings.index')),
        ];
    }
}
