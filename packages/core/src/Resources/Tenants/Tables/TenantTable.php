<?php

namespace Froxlor\Core\Resources\Tenants\Tables;

use Froxlor\Core\Resources\Tenants\Environment\Schemas\EnvironmentSchema;
use Froxlor\UI\Tables;

class TenantTable
{

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('plan.name')
                ->label('Plan'),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable(),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('resources.tenants.create'))
                ->icon(fn() => 'plus'),
        ];
    }
}
