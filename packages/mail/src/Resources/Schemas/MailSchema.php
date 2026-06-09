<?php

namespace Froxlor\Mail\Resources\Schemas;

use Froxlor\Domain\Models\Domain;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;
use Froxlor\UI\Tables;

class MailSchema
{
    public static function indexSchema(): array
    {
        return [
            'columns' => [
                Tables\Columns\TextColumn::make('address')
                    ->label(trans('froxlor-mail::generic.address'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('froxlor-core::generic.created_at'))
                    ->sortable(),
            ],
            'actions' => [
            ]
        ];
    }

    public static function showSchema(Domain $domain): array
    {
        return [
            'columns' => [
                Schemas\Components\Section::make('main')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label(trans('froxlor-core::generic.name'))
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label(trans('froxlor-core::generic.description')),
                    ]),

                Schemas\Components\Section::make('resources')
                    ->title(trans('froxlor-core::generic.resources'))
                    ->components([
                        Forms\Components\Dump::make('demo')
                            ->default(fn() => $domain->domain_vhosts()->get()->toArray()),
                    ]),
            ],
            'actions' => [
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.domains.index')),
            ]
        ];
    }
}
