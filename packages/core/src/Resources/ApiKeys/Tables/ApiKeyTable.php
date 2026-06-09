<?php

namespace Froxlor\Core\Resources\ApiKeys\Tables;

use Froxlor\UI\Tables;

class ApiKeyTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('tokenable.name')
                ->label(trans('froxlor-core::generic.owner')),

            Tables\Columns\TextColumn::make('last_used_at')
                ->label(trans('froxlor-core::generic.updated_at'))
                ->toggleable(isHiddenByDefault: true),

            Tables\Columns\TextColumn::make('expires_at')
                ->label(trans('froxlor-core::generic.expires_at'))
                ->toggleable(isHiddenByDefault: true),

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
                ->href(route('auth.api-keys.create'))
                ->icon('plus'),
        ];
    }
}
