<?php

namespace Froxlor\Core\Resources\Users\Tables;

use Froxlor\UI\Tables;

class UserTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->label(trans('froxlor-core::generic.email'))
                ->toggleable(isHiddenByDefault: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable()
                ->dateTime(),
        ];
    }

    public static function columnActions(): array
    {
        return [
            Tables\ColumnActions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->intendedRoute('auth.users.edit', ['user' => '{id}'])
                ->icon('eye'),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('auth.users.create'))
                ->icon('plus'),
        ];
    }
}
