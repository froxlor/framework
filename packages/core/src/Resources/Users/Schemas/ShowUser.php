<?php

namespace Froxlor\Core\Resources\Users\Schemas;

use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\AuditLogs\Tables\AuditLogTable;
use Froxlor\Core\Resources\Plans\Tables\PlanTable;
use Froxlor\Core\Resources\Roles\Tables\RoleTable;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;
use Froxlor\UI\Tables;

class ShowUser
{
    public static function schema(User $user): array
    {
        return [
            Schemas\Components\Tabs::make('tenants.show.tabs')
                ->props(['user' => $user])
                ->components([
                    Schemas\Components\Tab::make('tenants.show.tabs.details')
                        ->sort(0001)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            // info widgets o.Ä.?
                        ]),

                    Schemas\Components\Tab::make('tenants.show.tabs.edit')
                        ->sort(0002)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('environments')
                                ->components([
                                    Schemas\Components\Section::make('section_a')
                                        ->title(trans('froxlor-core::generic.title'))
                                        ->components([
                                            Forms\Components\TextInput::make('name')
                                                ->label(trans('froxlor-core::generic.name'))
                                                ->required(),

                                            Forms\Components\TextInput::make('description')
                                                ->label(trans('froxlor-core::generic.title')),
                                        ]),
                                ]),
                        ]),

                    Schemas\Components\Tab::make('tenants.show.tabs.environments')
                        ->sort(1000)
                        ->label(trans('froxlor-core::generic.environments'))
                        ->components([
                            Schemas\Components\Relation::make('environments')
                                ->fetch(route('api.tenants.environments.index', $tenant))
                                ->intendedRoute('tenants.environments.show', ['tenant' => $tenant->id, 'environment' => '{id}'])
                                ->columns([
                                    Tables\Columns\TextColumn::make('name')
                                        ->label(trans('froxlor-core::generic.name'))
                                        ->sortable(),
                                ]),
                        ]),

                    Schemas\Components\Tab::make('tenants.show.tabs.plans')
                        ->sort(2000)
                        ->label(trans('froxlor-core::generic.plans'))
                        ->components([
                            Schemas\Components\Relation::make('plans')
                                ->fetch(route('api.tenants.plans.index', $tenant))
                                //  ->intendedRoute('tenants.plans.show', ['tenant' => $tenant->id, 'plan' => '{id}'])
                                ->columns(PlanTable::columns()['columns'])
                                ->actions(PlanTable::columns()['actions']),
                        ]),

                    Schemas\Components\Tab::make('tenants.show.tabs.roles')
                        ->sort(2100)
                        ->label(trans('froxlor-core::generic.roles'))
                        ->components([
                            Schemas\Components\Relation::make('roles')
                                ->fetch(route('api.tenants.roles.index', $tenant))
                                //  ->intendedRoute('tenants.plans.show', ['tenant' => $tenant->id, 'plan' => '{id}'])
                                ->columns(RoleTable::columns()['columns'])
                                ->actions(RoleTable::columns()['actions']),
                        ]),

                    Schemas\Components\Tab::make('tenants.show.tabs.log')
                        ->sort(9999)
                        ->label(trans('froxlor-core::generic.audit-log'))
                        ->components([
                            Schemas\Components\Relation::make('audit_logs')
                                ->fetch(route('api.tenants.audit-log.index', $tenant))
                                ->columns(AuditLogTable::columns())
                                ->actions(AuditLogTable::actions()),
                        ]),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [];
    }
}
