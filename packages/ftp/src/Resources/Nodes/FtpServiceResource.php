<?php

namespace Froxlor\Ftp\Resources\Nodes;

use Froxlor\Core\Models\Node;
use Froxlor\Ftp\Resources\Nodes\Relations\FtpServices\Schemas\FtpServiceForm;
use Froxlor\Ftp\Resources\Nodes\Relations\FtpServices\Schemas\FtpServiceView;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas;
use Froxlor\UI\Schemas\Schema;

class FtpServiceResource extends Resource
{
    public function create(Node $node): Schema
    {
        return Schema::make('resources.nodes.ftp-service.create')
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - FTP service')
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.nodes.ftp-service.store', ['node' => $node]))
            ->intendedRoute('resources.nodes.ftp-service.show', ['node' => $node])
            ->components(FtpServiceForm::schema())
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.nodes.show', ['node' => $node])),
            ]);
    }

    public function show(Node $node): Schema
    {
        $ftpService = $node->ftpService;

        abort_if(! $ftpService, 404);

        return Schema::make('resources.nodes.ftp-service.show')
            ->props([
                'node' => $node,
                'ftpService' => $ftpService,
            ])
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - FTP service')
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.nodes.ftp-service.show', ['node' => $node]))
            ->components(FtpServiceView::schema($node, $ftpService))
            ->actions(FtpServiceView::actions($node));
    }

    public function edit(Node $node): Schema
    {
        $ftpService = $node->ftpService;

        abort_if(! $ftpService, 404);

        return Schema::make('resources.nodes.ftp-service.edit')
            ->teaser(trans('froxlor-core::generic.node'))
            ->title($node->name . ' - FTP service')
            ->description(trans('froxlor-core::generic.edit_resource'))
            ->fetch(route('api.nodes.ftp-service.show', ['node' => $node]))
            ->push(route('api.nodes.ftp-service.update', ['node' => $node]), 'PUT')
            ->intendedRoute('resources.nodes.ftp-service.show', ['node' => $node])
            ->components(FtpServiceForm::schema())
            ->actions([
                Schemas\Actions\Action::make('back')
                    ->label(trans('froxlor-core::generic.back'))
                    ->href(route('resources.nodes.ftp-service.show', ['node' => $node])),
            ]);
    }
}
