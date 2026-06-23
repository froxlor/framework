<?php

namespace Froxlor\Core\Resources\Plans\Relations\Resources\Schemas;

use Froxlor\Core\Models\Plan;
use Froxlor\UI\Tables;

class ResourceTable
{
    public static function columns(Plan $plan): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('pivot.limit')
                ->label(trans('froxlor-core::generic.limit'))
                ->sortable(),

        ];
    }

    public static function actions(Plan $plan): array
    {
        return [];
    }
}
