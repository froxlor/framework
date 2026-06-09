<?php

namespace Froxlor\Core\Resources\Overview;

use Froxlor\Core\Resources\Overview\Schemas\OverviewSchema;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;

class OverviewResource extends Resource
{
    public function index(): Schema
    {
        return Schema::make('overview')
            ->teaser(trans('froxlor-core::generic.dashboard'))
            ->title(trans('froxlor-core::generic.overview'))
            ->description(trans('froxlor-core::generic.overview_description'))
            ->components(OverviewSchema::schema())
            ->actions([
                Schemas\Actions\Action::make('create-user')
                    ->label(trans('froxlor-core::generic.user'))
                    ->href(route('auth.users.create')),

                Schemas\Actions\Action::make('create-tenant')
                    ->label(trans('froxlor-core::generic.tenant'))
                    ->href(route('resources.tenants.create')),

                Schemas\Actions\Action::make('create-node')
                    ->label(trans('froxlor-core::generic.node'))
                    ->href(route('resources.nodes.create')),
            ])
            ->cols(3);
    }
}
