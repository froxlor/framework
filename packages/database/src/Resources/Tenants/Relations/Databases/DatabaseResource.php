<?php

namespace Froxlor\Database\Resources\Tenants\Relations\Databases;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Database\Models\Database;
use Froxlor\Database\Resources\Tenants\Relations\Databases\Schemas\DatabaseForm;
use Froxlor\Database\Resources\Tenants\Relations\Databases\Schemas\DatabaseView;
use Froxlor\Database\Resources\Tenants\Relations\Databases\Tables\DatabaseTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables\Table;

class DatabaseResource extends Resource
{
    public function index(Tenant $tenant, Environment $environment): Table
    {
        return Table::make()
            ->title('Databases')
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => 'databases']))
            ->fetch(route('api.tenants.environments.databases.index', [
                'tenant' => $tenant,
                'environment' => $environment,
            ]))
            ->intendedRoute('tenants.environments.databases.show', [
                'tenant' => $tenant->id,
                'environment' => $environment->id,
                'database' => '{id}',
            ])
            ->columns(DatabaseTable::columns())
            ->columnActions(DatabaseTable::columnActions($environment))
            ->actions(DatabaseTable::actions($environment));
    }

    public function create(Tenant $tenant, Environment $environment): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.environment'))
            ->title($environment->name . ' - ' . trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.environments.databases.store', [
                'tenant' => $tenant,
                'environment' => $environment,
            ]))
            ->intendedRoute('tenants.environments.databases.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => '{id}',
            ])
            ->components(DatabaseForm::schema($environment))
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.environments.show', ['tenant' => $tenant, 'environment' => $environment])),
            ]);
    }

    public function show(Tenant $tenant, Environment $environment, Database $database): Schema
    {
        return Schema::make('tenants.environments.databases.show')
            ->props([
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => $database,
            ])
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.environment'))
            ->title($environment->name . ' - ' . $database->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.tenants.environments.databases.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => $database,
            ]))
            ->components(DatabaseView::schema($tenant, $environment, $database))
            ->actions(DatabaseView::actions($tenant, $environment, $database));
    }

    public function edit(Tenant $tenant, Environment $environment, Database $database): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.environment'))
            ->title($environment->name . ' - ' . $database->name)
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.tenants.environments.databases.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => $database,
            ]))
            ->push(route('api.tenants.environments.databases.update', [
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => $database,
            ]), 'PUT')
            ->intendedRoute('tenants.environments.databases.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'database' => $database,
            ])
            ->components(DatabaseForm::schema($environment))
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.environments.databases.show', [
                        'tenant' => $tenant,
                        'environment' => $environment,
                        'database' => $database,
                    ])),
            ]);
    }
}
