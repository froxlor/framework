<?php

namespace Froxlor\Packages\Resources;

use Froxlor\UI\Forms;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Schemas\Schema;

class MarketplaceCredentialsResource extends Resource
{
    public function edit(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-packages::generic.marketplace_credentials'))
            ->description(trans('froxlor-packages::generic.marketplace_credentials_description'))
            ->fetch(route('api.packages.marketplace-credentials.show'))
            ->push(route('api.packages.marketplace-credentials.update'), 'PUT')
            ->intendedRoute('packages.discovery.index')
            ->components([
                Section::make('credentials')
                    ->title(trans('froxlor-packages::generic.marketplace_credentials'))
                    ->components([
                        Forms\Components\TextInput::make('username')
                            ->label(trans('froxlor-core::generic.username')),

                        Forms\Components\TextInput::make('token')
                            ->label(trans('froxlor-packages::generic.token'))
                            ->password()
                            ->required(),
                    ]),
            ])
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('packages.discovery.index')),
            ]);
    }
}
