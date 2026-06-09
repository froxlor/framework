<?php

namespace Froxlor\Core\Resources\Roles;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Resources\Roles\Schemas\RoleForm;
use Froxlor\Core\Resources\Roles\Schemas\ShowRole;
use Froxlor\Core\Resources\Roles\Tables\RoleTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class RoleResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.roles'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.roles')]))
            ->fetch(route('api.roles.index'))
            ->intendedRoute('auth.roles.show', ['role' => '{id}'])
            ->columns(RoleTable::columns())
            ->columnActions([
                Tables\ColumnActions\Action::make('view')
                    ->label(trans('froxlor-core::generic.view'))
                    ->intendedRoute('auth.roles.show', ['role' => '{id}'])
                    ->icon('eye'),
            ])
            ->actions(RoleTable::actions());
    }

    public function show(Role $role): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.role'))
            ->title($role->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.roles.show', $role))
            ->push(route('api.roles.update', $role), 'PUT')
            ->intendedRoute('auth.roles.show', ['role' => $role])
            ->components(ShowRole::schema($role))
            ->actions(ShowRole::actions($role));
    }

    public function create(): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.role'))
            ->title(trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.roles.store'))
            ->intendedRoute('auth.roles.show', ['role' => '{id}'])
            ->components(RoleForm::schema(false))
            ->cols(3)
            ->actions(RoleForm::createActions());
    }

    public function edit(Role $role): Schema
    {
        return $this->create()
            ->teaser(trans('froxlor-core::generic.role'))
            ->title($role->name)
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.roles.show', $role))
            ->push(route('api.roles.update', $role), 'PUT')
            ->intendedRoute('auth.roles.show', ['role' => $role])
            ->components(RoleForm::schema())
            ->cols(3)
            ->actions(RoleForm::editActions($role));
    }
}
