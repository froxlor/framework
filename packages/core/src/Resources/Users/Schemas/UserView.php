<?php

namespace Froxlor\Core\Resources\Users\Schemas;

use Froxlor\Core\Models\User;
use Froxlor\UI\Schemas;

class UserView
{
    public static function schema(User $user, ?string $tenantId = null): array
    {
        return [
            Schemas\Components\Tabs::make('auth.users.show.tabs')
                ->props(['user' => $user])
                ->components([
                    Schemas\Components\Tab::make('auth.users.show.tabs.details')
                        ->sort(1)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            Schemas\Schema::make('auth.users.details')
                                ->components([
                                    Schemas\Components\Group::make('auth.users.show.details.group_a')
                                        ->components([
                                            Schemas\Components\Section::make('auth.users.show.details.profile')
                                                ->title(trans('froxlor-core::generic.details'))
                                                ->description(trans('froxlor-core::generic.user'))
                                                ->components([
                                                    Schemas\Components\Text::make('name')
                                                        ->label(trans('froxlor-core::generic.name')),

                                                    Schemas\Components\Text::make('first_name')
                                                        ->label(trans('froxlor-core::generic.first_name')),

                                                    Schemas\Components\Text::make('last_name')
                                                        ->label(trans('froxlor-core::generic.last_name')),

                                                    Schemas\Components\Text::make('company_name')
                                                        ->label(trans('froxlor-core::generic.company_name')),

                                                    Schemas\Components\Text::make('email')
                                                        ->label(trans('froxlor-core::generic.email')),
                                                ]),

                                            Schemas\Components\Section::make('auth.users.show.details.access')
                                                ->title(trans('froxlor-core::generic.tenant'))
                                                ->description(trans('froxlor-core::generic.permissions'))
                                                ->components([
                                                    Schemas\Components\Text::make('tenant.name')
                                                        ->label(trans('froxlor-core::generic.tenant'))
                                                        ->default(fn(User $user) => $user->tenants->first()?->name ?? trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('role.name')
                                                        ->label(trans('froxlor-core::generic.role'))
                                                        ->default(fn(User $user) => $user->roles->first()?->name ?? trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('plan.name')
                                                        ->label(trans('froxlor-core::generic.plan'))
                                                        ->default(trans('froxlor-core::generic.none')),

                                                    Schemas\Components\Text::make('tenant_count')
                                                        ->label(trans('froxlor-core::generic.tenants'))
                                                        ->default(fn(User $user) => $user->tenants->count()),

                                                    Schemas\Components\Text::make('environment_count')
                                                        ->label(trans('froxlor-core::generic.environments'))
                                                        ->default(fn(User $user) => $user->environments->count()),
                                                ]),

                                            Schemas\Components\Section::make('auth.users.show.details.assignments')
                                                ->title(trans('froxlor-core::generic.resources'))
                                                ->description(trans('froxlor-core::generic.overview'))
                                                ->components([
                                                    Schemas\Components\Text::make('tenant_names')
                                                        ->label(trans('froxlor-core::generic.tenants'))
                                                        ->default(fn(User $user) => $user->tenants->pluck('name')->join(', ')),

                                                    Schemas\Components\Text::make('environment_names')
                                                        ->label(trans('froxlor-core::generic.environments'))
                                                        ->default(fn(User $user) => $user->environments->pluck('name')->join(', ')),

                                                    Schemas\Components\Text::make('role_names_summary')
                                                        ->label(trans('froxlor-core::generic.roles'))
                                                        ->default(fn(User $user) => $user->roles->pluck('name')->join(', ')),
                                                ]),
                                        ])
                                        ->colSpan(2),

                                    Schemas\Components\Group::make('auth.users.show.details.group_b')
                                        ->components([
                                            Schemas\Components\Section::make('auth.users.show.details.meta')
                                                ->title(trans('froxlor-core::generic.title'))
                                                ->description('Lifecycle metadata')
                                                ->components([
                                                    Schemas\Components\Text::make('id')
                                                        ->label('ID'),

                                                    Schemas\Components\Text::make('created_at')
                                                        ->label(trans('froxlor-core::generic.created_at')),

                                                    Schemas\Components\Text::make('email_verified_at')
                                                        ->label(trans('froxlor-core::generic.email_verified_at')),

                                                    Schemas\Components\Text::make('updated_at')
                                                        ->label(trans('froxlor-core::generic.updated_at')),
                                                ]),
                                        ]),
                                ])
                                ->cols(3),
                        ]),

                    Schemas\Components\Tab::make('auth.users.show.tabs.edit')
                        ->sort(2)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('auth.users.edit')
                                ->components(EditUserForm::schema($tenantId))
                                ->cols(3),
                        ]),
                ]),
        ];
    }

    public static function actions(User $user): array
    {
        return [
            Schemas\Actions\Action::make('edit')
                ->label(trans('froxlor-core::generic.edit'))
                ->href(route('auth.users.edit', ['user' => $user])),

            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => trans('froxlor-core::generic.users')]))
                ->href(route('auth.users.index')),
        ];
    }
}
