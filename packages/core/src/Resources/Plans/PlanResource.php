<?php

namespace Froxlor\Core\Resources\Plans;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Resources\Plans\Schemas\PlanForm;
use Froxlor\Core\Resources\Plans\Schemas\PlanView;
use Froxlor\Core\Resources\Plans\Tables\PlanTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables\Table;

class PlanResource extends Resource
{
    public function index(): Table
    {
        $plansIndexSchema = PlanTable::columns();
        return Table::make()
            ->title(trans('froxlor-core::generic.plans'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.plans')]))
            ->fetch(route('api.plans.index'))
            ->intendedRoute('resources.plans.show', ['plan' => '{id}'])
            ->columns(PlanTable::columns())
            ->actions(PlanTable::actions());
    }

    public function show(Plan $plan): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.view_resource'))
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.plans.show', $plan))
            ->push(route('api.plans.update', $plan))
            ->intendedRoute('resources.plans.index')
            ->components(PlanView::schema($plan))
            ->actions(PlanView::actions($plan));
    }

    public function create(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.edit_resource'))
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->push(route('api.nodes.store'))
            ->intendedRoute('resources.plans.index')
            ->components(PlanForm::schema())
            ->actions(PlanForm::actions());
    }

    public function edit(Plan $plan): Schema
    {
        return $this->create()
            ->fetch(route('api.plans.show', $plan))
            ->push(route('api.plans.update', $plan), 'PUT');
    }
}
