<?php

namespace Froxlor\Core\Resources\Tenants\Relations\Environments;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\Environments\Schemas\EnvironmentView;
use Froxlor\Core\Resources\Tenants\Relations\Environments\Tables\EnvironmentTable;
use Froxlor\UI\Forms;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables\Table;
use Illuminate\Http\Request;

class EnvironmentResource extends Resource
{
    public function index(Request $request, Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.environments'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.tenants') . ' ' . trans('froxlor-core::generic.environments')]))
            ->fetch(route('api.tenants.environments.index', ['tenant' => $tenant]))
            ->intendedRoute('tenants.environments.show', ['tenant' => $tenant->id, 'environment' => '{id}'])
            ->columns(EnvironmentTable::columns($tenant))
            ->actions(EnvironmentTable::actions($tenant));
    }

    public function create(Tenant $tenant): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.environments.store', ['tenant' => $tenant]))
            ->intendedRoute('tenants.show', ['tenant' => $tenant])
            ->components([
                Section::make('section_a')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label(trans('froxlor-core::generic.name'))
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label(trans('froxlor-core::generic.description')),
                    ]),

                Section::make('section_b')
                    ->title(trans('froxlor-core::generic.title'))
                    ->components([
                        Forms\Components\TextInput::make('plan_id')
                            ->label('Plan ID'),

                        Forms\Components\TextInput::make('node_id')
                            ->label('Node ID'),
                    ]),
            ])
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.show', ['tenant' => $tenant])),
            ]);
    }

    public function show(Tenant $tenant, Environment $environment): Schema
    {
        return Schema::make('tenants.environments.show')
            ->props(['tenant' => $tenant, 'environment' => $environment])
            ->teaser(trans('froxlor-core::generic.tenant') . ' - ' . trans('froxlor-core::generic.environment'))
            ->title($tenant->name . ' - ' . $environment->name)
            ->fetch(route('api.tenants.environments.show', [$tenant, $environment]))
            ->components(EnvironmentView::schema($tenant, $environment))
            ->actions(EnvironmentView::actions($tenant, $environment));
    }

    public function edit(Tenant $tenant, Environment $environment): Schema
    {
        return $this->create($tenant)
            ->fetch(route('api.tenants.environments.show', ['tenant' => $tenant, 'environment' => $environment]))
            ->push(route('api.tenants.environments.update', ['tenant' => $tenant, 'environment' => $environment]), 'PUT')
            ->intendedRoute('tenants.environments.show', ['tenant' => $tenant, 'environment' => $environment]);
    }
}
