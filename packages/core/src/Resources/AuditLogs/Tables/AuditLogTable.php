<?php

namespace Froxlor\Core\Resources\AuditLogs\Tables;

use Froxlor\UI\Tables\Columns\TextColumn;

class AuditLogTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('action')
                ->label(trans('froxlor-core::generic.action'))
                ->sortable(),

            TextColumn::make('tenant.name')
                ->label(trans('froxlor-core::generic.tenant'))
                ->searchable(),

            TextColumn::make('environment.name')
                ->label(trans('froxlor-core::generic.environment')),

            TextColumn::make('created_at')
                ->label(trans('froxlor-core::generic.created_at'))
                ->sortable(),
        ];
    }

    public static function actions(): array
    {
        return [];
    }
}
