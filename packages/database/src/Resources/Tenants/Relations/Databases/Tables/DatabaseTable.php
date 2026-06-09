<?php

namespace Froxlor\Database\Resources\Tenants\Relations\Databases\Tables;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\UI\Tables;

class DatabaseTable
{
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(trans('froxlor-core::generic.name'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('database_name')
                ->label('Database name')
                ->sortable(),
            Tables\Columns\TextColumn::make('username')
                ->label(trans('froxlor-core::generic.username'))
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->dateTime()
                ->sortable(),
        ];
    }

    public static function columnActions(Environment $environment): array
    {
        return [
            Tables\ColumnActions\Action::make('view')
                ->label(trans('froxlor-core::generic.view'))
                ->intendedRoute('tenants.environments.databases.show', [
                    'tenant' => $environment->tenant_id,
                    'environment' => $environment->id,
                    'database' => '{id}',
                ])
                ->icon('eye'),
        ];
    }

    public static function actions(Environment $environment): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label(trans('froxlor-core::generic.create'))
                ->href(route('tenants.environments.databases.create', [
                    'tenant' => $environment->tenant_id,
                    'environment' => $environment,
                ]))
                ->visible(fn() => self::mainNodeHasDatabaseService($environment))
                ->icon('plus'),
        ];
    }

    private static function mainNodeHasDatabaseService(Environment $environment): bool
    {
        /** @var Node|null $node */
        $node = $environment->nodes()->wherePivot('mode', 'main')->first() ?? $environment->nodes()->first();

        return $node?->databaseServer !== null;
    }
}
