<?php

namespace Froxlor\Core\Resources\Users;

use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\Users\Schemas\CreateUserForm;
use Froxlor\Core\Resources\Users\Schemas\EditUserForm;
use Froxlor\Core\Resources\Users\Schemas\UserView;
use Froxlor\Core\Resources\Users\Tables\UserTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class UserResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.users'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.users')]))
            ->fetch(route('api.users.index'))
            ->intendedRoute('auth.users.show', ['user' => '{id}'])
            ->columns(UserTable::columns())
            ->columnActions([
                Tables\ColumnActions\Action::make('view')
                    ->label(trans('froxlor-core::generic.view'))
                    ->intendedRoute('auth.users.show', ['user' => '{id}'])
                    ->icon('eye'),
            ])
            ->actions(UserTable::actions());
    }

    public function create(): Schema
    {
        $tenantId = request()->query('tenant');

        return Schema::make()
            ->teaser(trans('froxlor-core::generic.user'))
            ->title(trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.users.store'))
            ->intendedRoute('auth.users.show', ['user' => '{id}'])
            ->components(CreateUserForm::schema($tenantId))
            ->actions(CreateUserForm::actions());
    }

    public function show(User $user): Schema
    {
        $user->load(['tenants', 'environments', 'roles']);
        $tenantId = $user->tenants->first()?->id;

        return Schema::make('auth.users.show')
            ->props(['user' => $user])
            ->teaser(trans('froxlor-core::generic.user'))
            ->title($user->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.users.show', $user))
            ->components(UserView::schema($user, $tenantId))
            ->actions(UserView::actions($user));
    }

    public function edit(User $user): Schema
    {
        $user->load('tenants');
        $tenantId = $user->tenants->first()?->id;

        return Schema::make()
            ->teaser(trans('froxlor-core::generic.user'))
            ->title($user->name)
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.users.show', $user))
            ->push(route('api.users.update', $user), 'PUT')
            ->intendedRoute('auth.users.show', ['user' => $user])
            ->components(EditUserForm::schema($tenantId))
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('auth.users.show', ['user' => $user])),
            ])
            ->cols(3);
    }
}
