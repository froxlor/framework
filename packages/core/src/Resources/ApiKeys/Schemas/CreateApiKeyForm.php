<?php

namespace Froxlor\Core\Resources\ApiKeys\Schemas;

use Froxlor\Core\Support\Api;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class CreateApiKeyForm
{
    public static function schema(): array
    {
        return [
            Schemas\Components\Section::make('section_a')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\Select::make('user_id')
                        ->label(trans('froxlor-core::generic.owner'))
                        ->options(self::userOptions())
                        ->default(fn() => auth()->id()),

                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),
                ]),

            Schemas\Components\Section::make('section_b')
                ->title(trans('froxlor-core::generic.configuration'))
                ->components([
                    Forms\Components\TextInput::make('abilities')
                        ->label(trans('froxlor-core::generic.abilities'))
                        ->default('*'),

                    Forms\Components\TextInput::make('expires_at')
                        ->label(trans('froxlor-core::generic.expires_at')),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('auth.api-keys.index')),
        ];
    }

    private static function userOptions(): array
    {
        try {
            $response = Api::request('GET', route('api.users.index'));
            $items = $response->data();
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($items as $item) {
            if (!isset($item['id'])) {
                continue;
            }

            $options[$item['id']] = $item['name'] ?? $item['email'] ?? $item['id'];
        }

        return $options;
    }
}
