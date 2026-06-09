<?php

namespace Froxlor\Core\Resources\Nodes\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\UI\Tables;

class NodeServiceActions
{
    public static function make(Node $node, string $routePrefix): array
    {
        return [
            Tables\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.backto', ['entity' => $node->name]))
                ->href(route('resources.nodes.show', ['node' => $node]))
                ->icon('circle-chevron-left'),
            Tables\Actions\Action::make('edit')
                ->label(trans('froxlor-core::generic.edit'))
                ->href(route($routePrefix . '.edit', ['node' => $node]))
                ->icon('pencil'),
            Tables\Actions\Action::make('install')
                ->label(trans('froxlor-core::generic.install'))
                ->href(route($routePrefix . '.install', ['node' => $node]))
                ->method('POST')
                ->confirm()
                ->icon('download'),
            Tables\Actions\Action::make('configure')
                ->label(trans('froxlor-core::generic.configure'))
                ->href(route($routePrefix . '.configure', ['node' => $node]))
                ->method('POST')
                ->confirm()
                ->icon('settings'),
            Tables\Actions\Action::make('check')
                ->label(trans('froxlor-core::generic.check'))
                ->href(route($routePrefix . '.check', ['node' => $node]))
                ->method('POST')
                ->confirm()
                ->icon('badge-check'),
        ];
    }
}
