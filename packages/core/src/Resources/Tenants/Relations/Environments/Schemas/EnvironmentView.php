<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Environments\Schemas;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\AuditLogs\Tables\AuditLogTable;
use Froxlor\Core\Resources\Plans\Tables\PlanTable;
use Froxlor\Core\Resources\Users\Tables\UserTable;
use Froxlor\UI\Schemas;
use Froxlor\UI\Tables;

class EnvironmentView
{
    public static function schema(Tenant $tenant, Environment $environment): array
    {
        return [
            Schemas\Components\Tabs::make('tenants.environments.show.tabs')
                ->props(['tenant' => $tenant, 'environment' => $environment])
                ->components([
                    Schemas\Components\Tab::make('tenants.environments.show.tabs.details')
                        ->sort(0001)
                        ->label(trans('froxlor-core::generic.details'))
                        ->components([
                            // info widgets o.Ä.?
                        ]),

                    Schemas\Components\Tab::make('tenants.environments.show.tabs.edit')
                        ->sort(0002)
                        ->label(trans('froxlor-core::generic.edit'))
                        ->components([
                            Schemas\Schema::make('environments')
                                ->components([
                                    Schemas\Components\Section::make('section_a')
                                        ->title(trans('froxlor-core::generic.title'))
                                        ->components([/* @todo self::editSchema() */]),
                                ]),
                        ]),

                    Schemas\Components\Tab::make('tenants.environments.show.tabs.users')
                        ->sort(0100)
                        ->label(trans('froxlor-core::generic.users'))
                        ->components([
                            Schemas\Components\Relation::make('users')
                                ->fetch(route('api.tenants.environments.users.index', [$tenant, $environment]))
                                //->intendedRoute('tenants.environments.users.show', ['tenant' => $tenant->id, 'environment' => '{id}'])
                                ->columns(UserTable::columns())
                                ->actions([]),
                        ]),
                    Schemas\Components\Tab::make('tenants.environments.show.tabs.plans')
                        ->sort(2000)
                        ->label(trans('froxlor-core::generic.plans'))
                        ->components([
                            Schemas\Components\Relation::make('plans')
                                ->fetch(route('api.tenants.environments.plans.index', [$tenant, $environment]))
                                //->intendedRoute('tenants.environments.plans.show', ['tenant' => $tenant->id, 'environment' => $environment->id, 'plan' => {id}'])
                                ->columns(PlanTable::columns())
                                ->actions([]),
                        ]),

                    Schemas\Components\Tab::make('tenants.environments.show.tabs.log')
                        ->sort(9999)
                        ->label(trans('froxlor-core::generic.audit-log'))
                        ->components([
                            Schemas\Components\Relation::make('auditlog')
                                ->fetch(route('api.tenants.environments.audit-log.index', [$tenant, $environment]))
                                //->intendedRoute('tenants.environments.users.show', ['tenant' => $tenant->id, 'environment' => '{id}'])
                                ->columns(AuditLogTable::columns())
                                ->actions(AuditLogTable::actions()),
                        ]),
                ]),
        ];
    }

    public static function actions(Tenant $tenant, Environment $environment): array
    {
        return [
            Tables\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $tenant->name]))
                ->href(route('tenants.show', ['tenant' => $tenant]))
                ->icon('circle-chevron-left'),
        ];
    }
}
