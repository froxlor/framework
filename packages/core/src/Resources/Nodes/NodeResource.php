<?php

namespace Froxlor\Core\Resources\Nodes;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Resources\Nodes\Schemas\NodeForm;
use Froxlor\Core\Resources\Nodes\Schemas\NodeView;
use Froxlor\Core\Resources\Nodes\Tables\NodeTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;

class NodeResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.nodes'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.nodes')]))
            ->fetch(route('api.nodes.index'))
            ->intendedRoute('resources.nodes.show', ['node' => '{id}'])
            ->redirectFirst(fn() => Node::query()->count() === 1)
            ->columns(NodeTable::columns())
            ->columnActions([
                Tables\ColumnActions\Action::make('view')
                    ->label(trans('froxlor-core::generic.view'))
                    ->intendedRoute('resources.nodes.show', ['node' => '{id}'])
                    ->icon('eye'),
            ])
            ->actions(NodeTable::actions());
    }

    public function create(): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.node'))
            ->title(trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.nodes.store'))
            ->intendedRoute('resources.nodes.show', ['node' => '{id}'])
            ->components(NodeForm::schema())
            ->cols(3)
            ->actions(NodeForm::actions());
    }

    public function show(Node $node): Schema
    {
        $node->load(['nodeInterfaces', 'environments.tenant']);

        return Schema::make()
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.nodes.show', $node))
            ->components(NodeView::schema($node))
            ->actions(NodeView::actions($node));
    }

    public function edit(Node $node): Schema
    {
        return $this->create()
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name)
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.nodes.show', $node))
            ->push(route('api.nodes.update', $node), 'PUT')
            ->intendedRoute('resources.nodes.show', ['node' => $node])
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.nodes.show', ['node' => $node])),
            ]);
    }
}
