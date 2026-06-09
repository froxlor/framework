<?php

namespace Froxlor\Packages\Resources;

use Froxlor\Packages\Models\Repository;
use Froxlor\UI\Forms;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class RepositoryResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-packages::generic.repositories'))
            ->description(trans('froxlor-packages::generic.repository_help'))
            ->fetch(route('api.repositories.index'))
            ->intendedRoute('packages.repositories.edit', ['repository' => '{id}'])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('froxlor-core::generic.name'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('enabled')
                    ->label(trans('froxlor-core::generic.enabled'))
                    ->trueIcon('circle-check')
                    ->falseIcon('circle-x')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(trans('froxlor-core::generic.type'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label(trans('froxlor-core::generic.url'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('verified')
                    ->label(trans('froxlor-packages::generic.verified'))
                    ->trueIcon('badge-check')
                    ->falseIcon('badge-question-mark')
                    ->trueVariant('primary')
                    ->falseVariant('warning'),
            ])
            ->columnActions([
                Tables\ColumnActions\Action::make('destroy')
                    ->label(trans('froxlor-packages::generic.delete'))
                    ->intendedRoute('packages.repositories.destroy', ['repository' => '{id}'])
                    ->visible(fn ($row) => !($row['enabled'] || $row['protected']))
                    ->variant('danger')
                    ->icon('trash')
                    ->method('DELETE'),
            ])
            ->actions([
                Tables\Actions\Action::make('stable')
                    ->label(trans('froxlor-packages::generic.stable'))
                    ->href(route('packages.repositories.switch', ['type' => 'stable']))
                    ->variant('secondary')
                    ->icon('shield')
                    ->method('post'),

                Tables\Actions\Action::make('developer')
                    ->label(trans('froxlor-packages::generic.developer'))
                    ->href(route('packages.repositories.switch', ['type' => 'developer']))
                    ->variant('secondary')
                    ->icon('bug')
                    ->method('post'),

                Tables\Actions\Action::make('update')
                    ->label(trans('froxlor-packages::generic.update_repositories'))
                    ->href(route('packages.repositories.update'))
                    ->variant('secondary')
                    ->icon('refresh-cw')
                    ->method('post'),

                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-packages::generic.add'))
                    ->href(route('packages.repositories.create'))
                    ->variant('primary')
                    ->icon('plus'),
            ]);
    }

    public function create(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create') . ' ' . trans('froxlor-packages::generic.repository'))
            ->description(trans('froxlor-packages::generic.repository_help'))
            ->push(route('api.repositories.store'))
            ->intendedRoute('packages.repositories.index')
            ->components([
                Schemas\Components\Section::make('repository')
                    ->title(trans('froxlor-packages::generic.repository'))
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label(trans('froxlor-core::generic.name'))
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label(trans('froxlor-core::generic.type'))
                            ->default('composer')
                            ->options([
                                'composer' => trans('froxlor-packages::generic.composer'),
                                'path' => trans('froxlor-packages::generic.path'),
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('url')
                            ->label(trans('froxlor-core::generic.url'))
                            ->required(),

                        Forms\Components\Boolean::make('enabled')
                            ->label(trans('froxlor-core::generic.enabled'))
                            ->toggle(),
                    ]),
            ])
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('packages.repositories.index')),
            ]);
    }

    public function edit(Repository $repository): Schema
    {
        return $this->create()
            ->title(trans('froxlor-core::generic.edit') . ' ' . trans('froxlor-packages::generic.repository'))
            ->description(trans('froxlor-packages::generic.repository_help'))
            ->fetch(route('api.repositories.show', $repository))
            ->push(route('api.repositories.update', $repository), 'PUT');
    }
}
