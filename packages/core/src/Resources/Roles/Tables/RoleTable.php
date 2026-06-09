<?php

namespace Froxlor\Core\Resources\Roles\Tables;

use Froxlor\UI\Tables;

class RoleTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('description')
                ->label(trans('froxlor-core::generic.description'))
                ->sortable(),

            Tables\Columns\TextColumn::make('members_count')
                ->label(trans('froxlor-core::generic.members_count'))
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable()
                ->dateTime(),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('auth.roles.create'))
                ->icon('plus'),
        ];
    }
}
