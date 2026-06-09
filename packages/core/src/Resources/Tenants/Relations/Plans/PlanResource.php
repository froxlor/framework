<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Plans;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\Plans\Schemas\PlanView;
use Froxlor\Core\Resources\Tenants\Relations\Plans\Tables\PlanTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables\Table;

class PlanResource extends Resource
{
    public function index(Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.plans'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.tenants') . ' ' . trans('froxlor-core::generic.plans')]))
            ->fetch(route('api.tenants.plans.index', ['tenant' => $tenant]))
            ->intendedRoute('tenants.plans.show', ['tenant' => $tenant->id, 'plan' => '{id}'])
            ->columns(PlanTable::columns($tenant))
            ->actions([]);
    }

    public function show(Tenant $tenant, Plan $plan): Schema
    {
        return Schema::make()
            ->props(['tenant' => $tenant, 'plan' => $plan])
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.plan'))
            ->title($tenant->name . ' - ' . $plan->name)
            ->fetch(route('api.tenants.plans.show', ['tenant' => $tenant, 'plan' => $plan]))
            ->push(route('api.tenants.plans.update', ['tenant' => $tenant, 'plan' => $plan]), 'PUT')
            ->intendedRoute('tenants.plans.index', ['tenant' => $tenant])
            ->components(PlanView::schema($tenant, $plan))
            ->actions(PlanView::actions($tenant, $plan));
    }

}
