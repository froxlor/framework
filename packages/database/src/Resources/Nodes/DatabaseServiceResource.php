<?php

namespace Froxlor\Database\Resources\Nodes;

use Froxlor\Core\Models\Node;
use Froxlor\Database\Resources\Nodes\Relations\DatabaseServers\Schemas\DatabaseServerForm;
use Froxlor\Database\Resources\Nodes\Relations\DatabaseServers\Schemas\DatabaseServerView;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;

class DatabaseServiceResource extends Resource
{
    public function create(Node $node): Schema
    {
        return Schema::make('resources.nodes.database-service.create')
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - Database service')
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.nodes.database-service.store', ['node' => $node]))
            ->intendedRoute('resources.nodes.database-service.show', ['node' => $node])
            ->components(DatabaseServerForm::schema())
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.nodes.show', ['node' => $node])),
            ]);
    }

    public function show(Node $node): Schema
    {
        $databaseService = $node->databaseServer;

        abort_if(! $databaseService, 404);

        return Schema::make('resources.nodes.database-service.show')
            ->props([
                'node' => $node,
                'databaseServer' => $databaseService,
            ])
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - Database service')
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.nodes.database-service.show', ['node' => $node]))
            ->components(DatabaseServerView::schema($node, $databaseService))
            ->actions(DatabaseServerView::actions($node, $databaseService));
    }

    public function edit(Node $node): Schema
    {
        $databaseService = $node->databaseServer;

        abort_if(! $databaseService, 404);

        return Schema::make('resources.nodes.database-service.edit')
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - Database service')
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.nodes.database-service.show', ['node' => $node]))
            ->push(route('api.nodes.database-service.update', ['node' => $node]), 'PUT')
            ->intendedRoute('resources.nodes.database-service.show', ['node' => $node])
            ->components(DatabaseServerForm::schema())
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.nodes.database-service.show', ['node' => $node])),
            ]);
    }
}
