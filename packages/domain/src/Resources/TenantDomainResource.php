<?php

namespace Froxlor\Domain\Resources;

use Froxlor\Core\Models\Tenant;
use Froxlor\Domain\Models\Domain;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class TenantDomainResource extends Resource
{
    public function index(Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-domain::generic.domains'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-domain::generic.domains')]))
            ->fetch(route('api.tenants.domains.index', ['tenant' => $tenant->id]))
            ->intendedRoute('tenants.domains.show', ['tenant' => $tenant->id, 'domain' => '{id}'])
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
                    ->href(route('tenants.domains.create', ['tenant' => $tenant]))
                    ->icon('plus'),
            ]);
    }

    public function show(Tenant $tenant, Domain $domain): Schema
    {
        return Schema::make()
            ->title('title')
            ->description('description')
            ->fetch(route('api.tenants.domains.show', ['tenant' => $tenant, 'domain' => $domain]))
            ->intendedRoute('tenants.domains.edit', ['tenant' => $tenant, 'domain' => $domain])
            ->components([
                \Froxlor\UI\Forms\Components\Dump::make('demo')
                    ->default(fn() => $domain->toArray()),
            ]);
    }

    public function create(Tenant $tenant): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.tenants.domains.store', ['tenant' => $tenant]))
            ->intendedRoute('tenants.show', ['tenant' => $tenant])
            ->components($this->domainFormSchema())
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('tenants.show', ['tenant' => $tenant])),
            ]);
    }

    public function edit(Tenant $tenant, Domain $domain): Schema
    {
        return $this->create($tenant)
            ->fetch(route('api.tenants.domains.show', ['tenant' => $tenant, 'domain' => $domain]))
            ->push(route('api.tenants.domains.update', ['tenant' => $tenant, 'domain' => $domain]), 'PUT')
            ->intendedRoute('tenants.domains.show', ['tenant' => $tenant, 'domain' => $domain]);
    }

    private function domainFormSchema(): array
    {
        return [
            \Froxlor\UI\Forms\Components\TextInput::make('domain')
                ->label(trans('froxlor-domain::generic.domain'))
                ->required(),

            \Froxlor\UI\Forms\Components\TextInput::make('parent_domain_id')
                ->label('Parent Domain ID'),

            \Froxlor\UI\Forms\Components\TextInput::make('environment_id')
                ->label('Environment ID'),

            \Froxlor\UI\Forms\Components\TextInput::make('node_id')
                ->label('Node ID'),
        ];
    }
}
