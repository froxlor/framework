<?php

namespace Froxlor\Core\Resources\Roles\Relations\Permissions\Tables;

use Froxlor\Core\Models\Role;
use Froxlor\UI\Tables;

class PermissionTable
{
    public static function columns(Role $role): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->sortable(),

            Tables\Columns\TextColumn::make('key')
                ->label(trans('froxlor-core::generic.key'))
                ->sortable(),
        ];
    }

    public static function actions(Role $role): array
    {
        return [];
    }
}
