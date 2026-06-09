<?php

namespace Froxlor\Core\Resources\Plans\Relations\Tenants\Tables;

use Froxlor\Core\Models\Tenant;
use Froxlor\UI\Tables;

class TenantTable
{
    public static function columns(Tenant $tenant): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('description')
                ->label(trans('froxlor-core::generic.description'))
                ->sortable(),

            // display "assigned" if $tenant->plan_id == {id}
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('resources.plans.create'))
                ->icon('plus'),
        ];
    }
}
