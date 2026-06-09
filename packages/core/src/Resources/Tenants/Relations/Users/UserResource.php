<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Users;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\Tenants\Relations\Users\Schemas\UserView;
use Froxlor\Core\Resources\Users\Schemas\CreateUserForm;
use Froxlor\Core\Resources\Users\Schemas\EditUserForm;
use Froxlor\Core\Resources\Users\Tables\UserTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class UserResource extends Resource
{
    public function index(Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.users'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.users')]))
            ->fetch(route('api.tenants.users.index', ['tenant' => $tenant]))
            ->intendedRoute('tenants.users.edit', ['tenant' => $tenant->id, 'user' => '{id}'])
            ->columns(UserTable::columns())
            ->columnActions([
                Tables\ColumnActions\Action::make('view')
                    ->label(trans('froxlor-core::generic.view'))
                    ->intendedRoute('tenants.users.show', ['tenant' => $tenant->id, 'user' => '{id}'])
                    ->icon('eye'),
            ])
            ->actions([
                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-core::generic.create'))
                    ->href(route('tenants.users.create', ['tenant' => $tenant]))
                    ->icon('plus'),
            ]);
    }

    public function create(Tenant $tenant): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.user'))
            ->title($tenant->name . ' - ' . trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.users.store', ['tenant' => $tenant]))
            ->intendedRoute('tenants.users.show', ['tenant' => $tenant, 'user' => '{id}'])
            ->components(CreateUserForm::schema($tenant->id, false))
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.users.index', ['tenant' => $tenant])),
            ]);
    }

    public function show(Tenant $tenant, User $user): Schema
    {
        return Schema::make('tenants.users.show')
            ->props(['tenant' => $tenant, 'user' => $user])
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.user'))
            ->title($tenant->name . ' - ' . $user->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.tenants.users.show', ['tenant' => $tenant, 'user' => $user]))
            ->components(UserView::schema($tenant, $user))
            ->actions(UserView::actions($tenant, $user));
    }

    public function edit(Tenant $tenant, User $user): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.user'))
            ->title($tenant->name . ' - ' . $user->name)
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.tenants.users.show', ['tenant' => $tenant, 'user' => $user]))
            ->push(route('api.tenants.users.update', ['tenant' => $tenant, 'user' => $user]), 'PUT')
            ->intendedRoute('tenants.users.show', ['tenant' => $tenant, 'user' => $user])
            ->components(EditUserForm::schema($tenant->id, false))
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.users.show', ['tenant' => $tenant, 'user' => $user])),
            ])
            ->cols(3);
    }
}
