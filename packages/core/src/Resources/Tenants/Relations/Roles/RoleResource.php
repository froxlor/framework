<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Roles;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
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
    public function index(Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.roles'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.roles')]))
            ->fetch(route('api.tenants.roles.index', ['tenant' => $tenant]))
            ->intendedRoute('tenants.roles.show', ['tenant' => $tenant->id, 'role' => '{id}'])
            ->columns(RoleTable::columns())
            ->actions([
                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-core::generic.create'))
                    ->href(route('tenants.roles.create', ['tenant' => $tenant]))
                    ->icon('plus'),
            ]);
    }

    public function show(Tenant $tenant, Role $role): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.view_resource'))
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.tenants.roles.show', ['tenant' => $tenant, 'role' => $role]))
            ->push(route('api.tenants.roles.update', ['tenant' => $tenant, 'role' => $role]), 'PUT')
            ->intendedRoute('tenants.roles.index', ['tenant' => $tenant])
            ->components(ShowRole::schema($role, $tenant))
            ->actions($this->showActions($tenant, $role));
    }

    public function create(Tenant $tenant): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.edit_resource'))
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->push(route('api.tenants.roles.store', ['tenant' => $tenant]))
            ->intendedRoute('tenants.roles.index', ['tenant' => $tenant])
            ->components(RoleForm::schema())
            ->actions($this->createActions($tenant));
    }

    public function edit(Tenant $tenant, Role $role): Schema
    {
        return $this->create($tenant)
            ->fetch(route('api.tenants.roles.show', ['tenant' => $tenant, 'role' => $role]))
            ->push(route('api.tenants.roles.update', ['tenant' => $tenant, 'role' => $role]), 'PUT')
            ->actions($this->editActions($tenant, $role));
    }

    private function showActions(Tenant $tenant, Role $role): array
    {
        return [
            Schemas\Actions\Action::make('edit')
                ->label(trans('froxlor-core::roles.edit_permissions'))
                ->href(route('tenants.roles.edit', ['tenant' => $tenant, 'role' => $role])),
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => trans('froxlor-core::generic.roles')]))
                ->href(route('tenants.roles.index', ['tenant' => $tenant])),
        ];
    }

    private function createActions(Tenant $tenant): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('tenants.roles.index', ['tenant' => $tenant])),
        ];
    }

    private function editActions(Tenant $tenant, Role $role): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('tenants.roles.show', ['tenant' => $tenant, 'role' => $role])),
        ];
    }
}
