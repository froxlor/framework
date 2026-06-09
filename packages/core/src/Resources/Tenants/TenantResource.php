<?php

namespace Froxlor\Core\Resources\Tenants;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Schemas\TenantForm;
use Froxlor\Core\Resources\Tenants\Schemas\TenantView;
use Froxlor\Core\Resources\Tenants\Tables\TenantTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables\Table;

class TenantResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.tenants'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.tenants')]))
            ->fetch(route('api.tenants.index'))
            ->intendedRoute('tenants.show', ['tenant' => '{id}'])
            ->columns(TenantTable::columns())
            ->actions(TenantTable::actions());
    }

    public function create(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.store'))
            ->intendedRoute('resources.tenants.index')
            ->components(TenantForm::schema())
            ->actions(TenantForm::actions());
    }

    public function show(Tenant $tenant): Schema
    {
        return Schema::make('tenants.show')
            ->props(['tenant' => $tenant])
            ->teaser(trans('froxlor-core::generic.tenant'))
            ->title($tenant->name)
            ->fetch(route('api.tenants.show', $tenant))
            ->components(TenantView::schema($tenant));
    }

    public function edit(Tenant $tenant): Schema
    {
        return $this->create()
            ->fetch(route('api.tenants.show', $tenant))
            ->push(route('api.tenants.update', $tenant), 'PUT');
    }
}
