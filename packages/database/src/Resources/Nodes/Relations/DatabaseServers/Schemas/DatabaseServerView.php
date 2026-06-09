<?php

namespace Froxlor\Database\Resources\Nodes\Relations\DatabaseServers\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Resources\Nodes\Schemas\NodeServiceActions;
use Froxlor\Core\Resources\Nodes\Schemas\NodeServiceSchema;
use Froxlor\Database\Models\DatabaseServer;
use Froxlor\UI\Schemas;

class DatabaseServerView
{
    public static function schema(Node $node, DatabaseServer $databaseServer): array
    {
        return [
            NodeServiceSchema::detailsTab(
                'resources.nodes.database-service.show.tabs',
                'Database server',
                [
                    Schemas\Components\Text::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->default(fn() => $databaseServer->name),
                    Schemas\Components\Text::make('driver')
                        ->label('Driver')
                        ->default(fn() => $databaseServer->driver),
                    Schemas\Components\Text::make('host')
                        ->label(trans('froxlor-core::generic.hostname'))
                        ->default(fn() => $databaseServer->host),
                    Schemas\Components\Text::make('port')
                        ->label('Port')
                        ->default(fn() => (string) $databaseServer->port),
                    Schemas\Components\Text::make('admin_username')
                        ->label('Admin username')
                        ->default(fn() => $databaseServer->admin_username ?: '-'),
                    ...NodeServiceSchema::standardStatusFields($databaseServer),
                    Schemas\Components\Text::make('databases_count')
                        ->label('Databases')
                        ->default(fn() => (string) $databaseServer->databases()->count()),
                ],
            ),
        ];
    }

    public static function actions(Node $node, DatabaseServer $databaseServer): array
    {
        return NodeServiceActions::make($node, 'resources.nodes.database-service');
    }
}
