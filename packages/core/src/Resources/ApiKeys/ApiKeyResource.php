<?php

namespace Froxlor\Core\Resources\ApiKeys;

use Froxlor\Core\Resources\ApiKeys\Schemas\ApiKeyView;
use Froxlor\Core\Resources\ApiKeys\Schemas\CreateApiKeyForm;
use Froxlor\Core\Resources\ApiKeys\Tables\ApiKeyTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Schemas\Schema;
use Froxlor\UI\Tables;
use Froxlor\UI\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.api_keys'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.api_keys')]))
            ->fetch(route('api.api-keys.index'))
            ->intendedRoute('auth.api-keys.show', ['api_key' => '{id}'])
            ->columns(ApiKeyTable::columns())
            ->selectionKey('id')
            ->bulkActions([
                Tables\Actions\Action::make('delete')
                    ->label(trans('froxlor-core::generic.delete'))
                    ->href(route('auth.api-keys.bulk-destroy'))
                    ->method('DELETE')
                    ->icon('trash')
                    ->destructive(),
            ])
            ->columnActions([
                Tables\ColumnActions\Action::make('view')
                    ->label(trans('froxlor-core::generic.view'))
                    ->intendedRoute('auth.api-keys.show', ['api_key' => '{id}'])
                    ->icon('eye'),
            ])
            ->actions(ApiKeyTable::actions());
    }

    public function create(): Schema
    {
        return Schema::make()
            ->teaser(trans('froxlor-core::generic.api_key'))
            ->title(trans('froxlor-core::generic.create'))
            ->description(trans('froxlor-core::generic.create_resource'))
            ->push(route('api.api-keys.store'))
            ->intendedRoute('auth.api-keys.show', ['api_key' => '{id}'])
            ->components(CreateApiKeyForm::schema())
            ->actions(CreateApiKeyForm::actions());
    }

    public function show(PersonalAccessToken $apiKey, ?string $plainTextToken = null): Schema
    {
        return Schema::make('auth.api-keys.show')
            ->props([
                'apiKey' => $apiKey,
                'plainTextToken' => $plainTextToken,
            ])
            ->teaser(trans('froxlor-core::generic.api_key'))
            ->title($apiKey->name)
            ->description(trans('froxlor-core::generic.view_resource'))
            ->fetch(route('api.api-keys.show', $apiKey))
            ->components(ApiKeyView::schema($apiKey))
            ->actions(ApiKeyView::actions($apiKey));
    }
}
