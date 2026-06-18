<?php

namespace Froxlor\Core\Resources\Roles\Relations\Permissions;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Resources\Roles\Relations\Permissions\Tables\PermissionTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Tables\Table;
use Illuminate\Http\Request;

class PermissionResource extends Resource
{
    public function index(Request $request, Role $role): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.permissions'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.roles') . ' ' . trans('froxlor-core::generic.permissions')]))
            ->fetch(route('api.roles.permissions.index', [$role]))
            //->intendedRoute('tenants.environments.edit', ['tenant' => $tenant->id, 'environment' => '{id}'])
            ->columns(PermissionTable::columns($role))
            ->actions(PermissionTable::actions($role));
    }
}
