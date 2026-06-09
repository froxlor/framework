<?php

namespace Froxlor\Core\Resources\Settings\Tables;

use Froxlor\UI\Tables;

class SettingTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('category')
                ->label(trans('froxlor-core::settings.category'))
                ->sortable(),

            Tables\Columns\TextColumn::make('key')
                ->label(trans('froxlor-core::settings.key'))
                ->sortable(),

            Tables\Columns\TextColumn::make('value')
                ->label(trans('froxlor-core::settings.value')),

            Tables\Columns\TextColumn::make('default_value')
                ->label(trans('froxlor-core::settings.default_value')),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable(),
        ];
    }
}
