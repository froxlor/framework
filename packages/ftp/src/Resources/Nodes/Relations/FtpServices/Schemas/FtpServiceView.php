<?php

namespace Froxlor\Ftp\Resources\Nodes\Relations\FtpServices\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Resources\Nodes\Schemas\NodeServiceActions;
use Froxlor\Core\Resources\Nodes\Schemas\NodeServiceSchema;
use Froxlor\Ftp\Models\FtpService;
use Froxlor\UI\Schemas;

class FtpServiceView
{
    public static function schema(Node $node, FtpService $ftpService): array
    {
        return [
            NodeServiceSchema::detailsTab(
                'resources.nodes.ftp-service.show.tabs',
                'FTP service',
                [
                    Schemas\Components\Text::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->default(fn() => $ftpService->name),
                    Schemas\Components\Text::make('driver')
                        ->label('Driver')
                        ->default(fn() => $ftpService->driver),
                    Schemas\Components\Text::make('listen_address')
                        ->label('Listen address')
                        ->default(fn() => $ftpService->listen_address),
                    Schemas\Components\Text::make('port')
                        ->label('Port')
                        ->default(fn() => (string) $ftpService->port),
                    Schemas\Components\Text::make('passive_range')
                        ->label('Passive range')
                        ->default(fn() => $ftpService->passive_min_port . ' - ' . $ftpService->passive_max_port),
                    ...NodeServiceSchema::standardStatusFields($ftpService),
                ],
            ),
        ];
    }

    public static function actions(Node $node): array
    {
        return NodeServiceActions::make($node, 'resources.nodes.ftp-service');
    }
}
