<?php

namespace Froxlor\Domain\Resources\Schemas;

use Froxlor\Domain\Models\Domain;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Tables;

class DomainSchema
{
    public static function indexSchema(): array
    {
        return [
            'columns' => [
                Tables\Columns\TextColumn::make('domain')
                    ->label(trans('froxlor-domain::generic.domain'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('properties')
                    ->label(trans('froxlor-domain::generic.properties'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('froxlor-core::generic.created_at'))
                    ->sortable(),
            ],
            'actions' => [
                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-core::generic.create'))
                    ->href(route('resources.domains.create'))
                    ->icon('plus'),
            ]
        ];
    }

    public static function showSchema(Domain $domain): array
    {
        return [
            'columns' => [
                Section::make('main')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        \Froxlor\UI\Forms\Components\TextInput::make('name')
                            ->label(trans('froxlor-core::generic.name'))
                            ->required(),

                        \Froxlor\UI\Forms\Components\TextInput::make('description')
                            ->label(trans('froxlor-core::generic.description')),
                    ]),

                Section::make('resources')
                    ->title(trans('froxlor-core::generic.resources'))
                    ->components([
                        \Froxlor\UI\Forms\Components\Dump::make('demo')
                            ->default(fn() => $domain->domain_vhosts()->get()->toArray()),
                    ]),
            ],
            'actions' => [
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('domains.index')),
            ]
        ];
    }
}
