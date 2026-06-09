<?php

namespace Froxlor\Domain\Resources;

use Froxlor\Domain\Models\Domain;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Components\Section;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class DomainResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-domain::generic.domains'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-domain::generic.domains')]))
            ->fetch(route('api.domains.index'))
            ->intendedRoute('resources.domains.show', ['domain' => '{id}'])
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
                    ->href(route('resources.domains.create'))
                    ->icon('plus'),
            ]);
    }

    public function show(Domain $domain): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.view_resource'))
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.domains.show', $domain))
            ->push(route('api.domains.update', $domain), 'PUT')
            ->intendedRoute('resources.domains.index')
            ->components($this->domainFormSchema())
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.domains.index')),
            ]);
    }

    public function create(): Schema
    {
        return Schema::make()
            ->title(trans('froxlor-core::generic.create_resource'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.domains.store'))
            ->intendedRoute('resources.domains.index')
            ->components($this->domainFormSchema())
            ->actions([
                \Froxlor\UI\Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.domains.index')),
            ]);
    }

    public function edit(Domain $domain): Schema
    {
        return $this->create()
            ->fetch(route('api.domains.show', $domain))
            ->push(route('api.domains.update', $domain), 'PUT')
            ->intendedRoute('resources.domains.show', ['domain' => $domain]);
    }

    private function domainFormSchema(): array
    {
        return [
            Section::make('main')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    \Froxlor\UI\Forms\Components\TextInput::make('domain')
                        ->label(trans('froxlor-domain::generic.domain'))
                        ->required(),

                    \Froxlor\UI\Forms\Components\TextInput::make('parent_domain_id')
                        ->label('Parent Domain ID'),
                ]),

            Section::make('relations')
                ->title(trans('froxlor-core::generic.title'))
                ->components([
                    \Froxlor\UI\Forms\Components\TextInput::make('tenant_id')
                        ->label('Tenant ID')
                        ->required(),

                    \Froxlor\UI\Forms\Components\TextInput::make('environment_id')
                        ->label('Environment ID'),

                    \Froxlor\UI\Forms\Components\TextInput::make('node_id')
                        ->label('Node ID'),
                ]),
        ];
    }
}
