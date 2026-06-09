<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Users\Schemas;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\Users\Schemas\EditUserForm;
use Froxlor\UI\Schemas;

class UserView
{
    public static function schema(Tenant $tenant, User $user): array
    {
        return [
            Schemas\Components\Tabs::make('tenants.users.show.tabs')
                ->props(['tenant' => $tenant, 'user' => $user])
                ->components([
                    Schemas\Components\Tab::make('tenants.users.show.tabs.details')
                        ->sort(1)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            Schemas\Schema::make('tenants.users.details')
                                ->components([
                                    Schemas\Components\Group::make('tenants.users.show.details.group_a')
                                        ->components([
                                            Schemas\Components\Section::make('tenants.users.show.details.profile')
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

                                            Schemas\Components\Section::make('tenants.users.show.details.access')
                                                ->title(trans('froxlor-core::generic.permissions'))
                                                ->description(trans('froxlor-core::generic.tenant'))
                                                ->components([
                                                    Schemas\Components\Text::make('tenant.name')
                                                        ->label(trans('froxlor-core::generic.tenant')),

                                                    Schemas\Components\Text::make('role.name')
                                                        ->label(trans('froxlor-core::generic.role')),

                                                    Schemas\Components\Text::make('plan.name')
                                                        ->label(trans('froxlor-core::generic.plan')),
                                                ]),
                                        ])
                                        ->colSpan(2),

                                    Schemas\Components\Group::make('tenants.users.show.details.group_b')
                                        ->components([
                                            Schemas\Components\Section::make('tenants.users.show.details.meta')
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

                    Schemas\Components\Tab::make('tenants.users.show.tabs.edit')
                        ->sort(2)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('tenants.users.edit')
                                ->components(EditUserForm::schema($tenant->id, false))
                                ->cols(3),
                        ]),
                ]),
        ];
    }

    public static function actions(Tenant $tenant, User $user): array
    {
        return [
            Schemas\Actions\Action::make('edit')
                ->label(trans('froxlor-core::generic.edit'))
                ->href(route('tenants.users.edit', ['tenant' => $tenant, 'user' => $user])),

            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $tenant->name]))
                ->href(route('tenants.show', ['tenant' => $tenant])),
        ];
    }
}
