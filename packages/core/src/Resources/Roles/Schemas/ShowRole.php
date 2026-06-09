<?php

namespace Froxlor\Core\Resources\Roles\Schemas;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Resources\Roles\Relations\Permissions\Tables\PermissionTable;
use Froxlor\Core\Resources\Users\Tables\UserTable;
use Froxlor\UI\Schemas;

class ShowRole
{
    public static function schema(Role $role): array
    {
        return [
            Schemas\Components\Tabs::make('roles.show.tabs')
                ->props(['role' => $role])
                ->components([
                    Schemas\Components\Tab::make('roles.show.tabs.details')
                        ->sort(1)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            Schemas\Schema::make('roles.details')
                                ->components([
                                    Schemas\Components\Group::make('roles.show.details.group_a')
                                        ->components([
                                            Schemas\Components\Section::make('roles.show.details.main')
                                                ->title(trans('froxlor-core::generic.details'))
                                                ->description(trans('froxlor-core::generic.role'))
                                                ->components([
                                                    Schemas\Components\Text::make('name')
                                                        ->label(trans('froxlor-core::generic.name')),

                                                    Schemas\Components\Text::make('description')
                                                        ->label(trans('froxlor-core::generic.description')),
                                                ]),

                                            Schemas\Components\Section::make('roles.show.details.assignments')
                                                ->title(trans('froxlor-core::generic.overview'))
                                                ->description(trans('froxlor-core::generic.permissions'))
                                                ->components([
                                                    Schemas\Components\Text::make('members_count')
                                                        ->label(trans('froxlor-core::generic.members_count')),

                                                    Schemas\Components\Text::make('permission_count')
                                                        ->label(trans('froxlor-core::generic.permissions'))
                                                        ->default(fn(Role $role) => $role->permissions->count()),

                                                    Schemas\Components\Text::make('user_names')
                                                        ->label(trans('froxlor-core::generic.users'))
                                                        ->default(fn(Role $role) => $role->users->pluck('name')->join(', ') ?: trans('froxlor-core::generic.none')),
                                                ]),
                                        ])
                                        ->colSpan(2),

                                    Schemas\Components\Group::make('roles.show.details.group_b')
                                        ->components([
                                            Schemas\Components\Section::make('roles.show.details.meta')
                                                ->title(trans('froxlor-core::generic.title'))
                                                ->description(trans('froxlor-core::generic.overview'))
                                                ->components([
                                                    Schemas\Components\Text::make('id')
                                                        ->label('ID'),

                                                    Schemas\Components\Text::make('created_at')
                                                        ->label(trans('froxlor-core::generic.created_at')),

                                                    Schemas\Components\Text::make('updated_at')
                                                        ->label(trans('froxlor-core::generic.updated_at')),
                                                ]),
                                        ]),
                                ])
                                ->cols(3),
                        ]),

                    Schemas\Components\Tab::make('roles.show.tabs.edit')
                        ->sort(2)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('roles.edit')
                                ->components(RoleForm::schema())
                                ->cols(3),
                        ]),

                    Schemas\Components\Tab::make('roles.show.tabs.permissions')
                        ->sort(100)
                        ->label(trans('froxlor-core::generic.permissions'))
                        ->components([
                            Schemas\Components\Relation::make('permissions')
                                ->fetch(route('api.roles.permissions.index', $role))
                                ->columns(PermissionTable::columns($role))
                                ->actions(PermissionTable::actions($role)),
                        ]),

                    Schemas\Components\Tab::make('roles.show.tabs.users')
                        ->sort(200)
                        ->label(trans('froxlor-core::generic.users'))
                        ->components([
                            Schemas\Components\Relation::make('users')
                                ->fetch(route('api.roles.users.index', $role))
                                ->intendedRoute('auth.users.show', ['user' => '{id}'])
                                ->columns(UserTable::columns())
                                ->actions([]),
                        ]),
                ]),
        ];
    }

    public static function actions(Role $role): array
    {
        return [
            Schemas\Actions\Action::make('edit_permissions')
                ->label(trans('froxlor-core::generic.permissions'))
                ->href(route('auth.roles.edit', [$role])),
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => trans('froxlor-core::generic.roles')]))
                ->href(route('auth.roles.index')),
        ];
    }
}
