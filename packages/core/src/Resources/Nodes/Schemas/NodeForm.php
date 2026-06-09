<?php

namespace Froxlor\Core\Resources\Nodes\Schemas;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Local;
use Froxlor\UI\Forms;
use Froxlor\UI\Schemas;

class NodeForm
{
    public static function schema(): array
    {
        return [
            Schemas\Components\Section::make('main')
                ->title(trans('froxlor-core::generic.details'))
                ->description(trans('froxlor-core::generic.node_system_description'))
                ->components([
                    Forms\Components\Select::make('adapter')
                        ->label(trans('froxlor-core::generic.adapter'))
                        ->options(fn() => array_map(fn($adapter) => trans($adapter::$name), Node::adapters()))
                        ->default(Local::class)
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->label(trans('froxlor-core::generic.name'))
                        ->default('Node ' . str()->random(4))
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label(trans('froxlor-core::generic.description')),
                ]),

            Schemas\Components\Section::make('machine')
                ->title(trans('froxlor-core::generic.network'))
                ->description(trans('froxlor-core::generic.node_network_form_description'))
                ->components([
                    Forms\Components\TextInput::make('hostname')
                        ->label(trans('froxlor-core::generic.hostname'))
                        ->required(),

                    Forms\Components\TextInput::make('username')
                        ->label(trans('froxlor-core::generic.username'))
                        ->required(),

                    Forms\Components\TextInput::make('password')
                        ->label(trans('froxlor-core::generic.password')),

                    Forms\Components\TextInput::make('sshkey')
                        ->label(trans('froxlor-core::generic.ssh_key')),

                    Forms\Components\Boolean::make('sudo')
                        ->label(trans('froxlor-core::generic.sudo'))
                        ->toggle(),

                    Forms\Components\Dump::make('properties')
                        ->label(trans('froxlor-core::generic.properties')),
                ]),
        ];
    }

    public static function actions(): array
    {
        return [
            Schemas\Actions\Action::make('back')
                ->label(trans('froxlor-core::generic.back'))
                ->href(route('resources.nodes.index')),
        ];
    }
}
