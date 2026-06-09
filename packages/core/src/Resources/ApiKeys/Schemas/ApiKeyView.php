<?php

namespace Froxlor\Core\Resources\ApiKeys\Schemas;

use Froxlor\UI\Schemas;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyView
{
    public static function schema(PersonalAccessToken $apiKey): array
    {
        return [
            Schemas\Components\Section::make('auth.api-keys.show.token')
                ->title(trans('froxlor-core::generic.plain_text_token'))
                ->description(trans('froxlor-core::generic.plain_text_token_description'))
                ->variant('warning')
                ->visible(fn(?string $plainTextToken = null) => filled($plainTextToken))
                ->components([
                    Schemas\Components\Text::make('plain_text_token')
                        ->label(trans('froxlor-core::generic.plain_text_token'))
                        ->default(fn(?string $plainTextToken = null) => $plainTextToken),
                ]),

            Schemas\Components\Section::make('auth.api-keys.show.details')
                ->title(trans('froxlor-core::generic.details'))
                ->description(trans('froxlor-core::generic.api_key'))
                ->components([
                    Schemas\Components\Text::make('name')
                        ->label(trans('froxlor-core::generic.name')),

                    Schemas\Components\Text::make('tokenable.name')
                        ->label(trans('froxlor-core::generic.owner')),

                    Schemas\Components\Text::make('abilities_summary')
                        ->label(trans('froxlor-core::generic.abilities'))
                        ->default(fn(PersonalAccessToken $apiKey) => collect($apiKey->abilities ?? [])->join(', ')),

                    Schemas\Components\Text::make('last_used_at')
                        ->label(trans('froxlor-core::generic.updated_at')),

                    Schemas\Components\Text::make('expires_at')
                        ->label(trans('froxlor-core::generic.expires_at')),

                    Schemas\Components\Text::make('created_at')
                        ->label(trans('froxlor-core::generic.created_at')),

                    Schemas\Components\Text::make('id')
                        ->label('ID'),
                ]),
        ];
    }

    public static function actions(PersonalAccessToken $apiKey): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => trans('froxlor-core::generic.api_keys')]))
                ->href(route('auth.api-keys.index')),

            Schemas\Actions\Action::make('delete')
                ->label(trans('froxlor-core::generic.delete'))
                ->href(route('auth.api-keys.destroy', ['api_key' => $apiKey]))
                ->method('DELETE')
                ->destructive()
                ->icon('trash'),
        ];
    }
}
