<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Plans\Tables;

use Froxlor\Core\Models\Tenant;
use Froxlor\UI\Tables;

class PlanTable
{
    public static function columns(Tenant $tenant): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable(),
        ];
    }

    public static function actions(Tenant $tenant): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('tenants.environments.create', ['tenant' => $tenant]))
                ->icon('plus'),
        ];
    }
}
