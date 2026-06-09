<?php

namespace Froxlor\Core\Resources\Roles\Relations\Permissions;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Resources\Roles\Relations\Permissions\Tables\PermissionTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
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

    public function create(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.permissions.store'))
            ->intendedRoute('auth.roles.index')
            ->components($this->permissionFormSchema())
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('auth.roles.index')),
            ]);
    }

    public function show(Role $role, Permission $permission): Schema
    {
        return Schema::make('roles.permissions.show')
            ->props(['role' => $role, 'permission' => $permission])
            ->title($permission->name)
            ->teaser(trans('froxlor-core::generic.permission'))
            ->fetch(route('api.permissions.show', $permission))
            ->components([
                Schemas\Components\Text::make('permission')
                    ->label(trans('froxlor-core::generic.details'))
                    ->default(fn() => $permission->toArray()),
            ])
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.backto', ['entity' => $role->name]))
                    ->href(route('auth.roles.show', $role)),
            ]);
    }

    public function edit(Role $role, Permission $permission): Schema
    {
        return $this->create()
            ->fetch(route('api.permissions.show', $permission))
            ->push(route('api.permissions.update', $permission), 'PUT')
            ->intendedRoute('auth.roles.show', $role);
    }

    private function permissionFormSchema(): array
    {
        return [
            Schemas\Components\Section::make('main')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    Forms\Components\TextInput::make('key')
                        ->label(trans('froxlor-core::generic.key'))
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(trans('froxlor-core::generic.description')),
                ]),
        ];
    }
}
