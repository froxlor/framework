<?php

namespace Froxlor\Domain\Resources;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models\Domain;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class EnvironmentDomainResource extends Resource
{
    public function index(Tenant $tenant, Environment $environment): Table
    {
        return Table::make()
            ->title(trans('froxlor-domain::generic.domains'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-domain::generic.domains')]))
            ->fetch(route('api.tenants.environments.domains.index', ['tenant' => $tenant, 'environment' => $environment]))
            ->intendedRoute('tenants.environments.domains.show', ['tenant' => $tenant, 'environment' => $environment, 'domain' => '{id}'])
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label(trans('froxlor-domain::generic.domain'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('info')
                    ->label(trans('froxlor-domain::generic.info')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('froxlor-core::generic.created_at'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('create')
                    ->label(trans('froxlor-core::generic.create'))
                    ->href(route('tenants.environments.domains.create', [
                        'tenant' => $tenant,
                        'environment' => $environment,
                    ]))
                    ->icon('plus'),
            ]);
    }

    public function show(Tenant $tenant, Environment $environment, Domain $domain): Schema
    {
        return Schema::make()
            ->title('title')
            ->description('description')
            ->fetch(route('api.tenants.environments.domains.show', ['tenant' => $tenant, 'environment' => $environment, 'domain' => $domain]))
            ->intendedRoute('tenants.environments.domains.edit', ['tenant' => $tenant->id, 'environment' => $environment->id, 'domain' => $domain->id])
            ->components([
                \Froxlor\UI\Forms\Components\Dump::make('demo')
                    ->default(fn() => $domain->toArray()),
            ]);
    }

    public function create(Tenant $tenant, Environment $environment): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.environments.domains.store', [
                'tenant' => $tenant,
                'environment' => $environment,
            ]))
            ->intendedRoute('tenants.environments.show', [
                'tenant' => $tenant,
                'environment' => $environment,
            ])
            ->components($this->domainFormSchema())
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.environments.show', [
                        'tenant' => $tenant,
                        'environment' => $environment,
                    ])),
            ]);
    }

    public function edit(Tenant $tenant, Environment $environment, Domain $domain): Schema
    {
        return $this->create($tenant, $environment)
            ->fetch(route('api.tenants.environments.domains.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'domain' => $domain,
            ]))
            ->push(route('api.tenants.environments.domains.update', [
                'tenant' => $tenant,
                'environment' => $environment,
                'domain' => $domain,
            ]), 'PUT')
            ->intendedRoute('tenants.environments.domains.show', [
                'tenant' => $tenant,
                'environment' => $environment,
                'domain' => $domain,
            ]);
    }

    private function domainFormSchema(): array
    {
        return [
            \Froxlor\UI\Forms\Components\TextInput::make('domain')
                ->label(trans('froxlor-domain::generic.domain'))
                ->required(),

            \Froxlor\UI\Forms\Components\TextInput::make('parent_domain_id')
                ->label('Parent Domain ID'),

            \Froxlor\UI\Forms\Components\TextInput::make('node_id')
                ->label('Node ID')
                ->required(),
        ];
    }
}
