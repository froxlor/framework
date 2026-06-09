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

            TextColumn::make('tenant')
                ->label(trans('froxlor-core::generic.tenant'))
                ->searchable()
                ->sortable(),

            TextColumn::make('environment')
                ->label(trans('froxlor-core::generic.environment'))
                ->sortable(),

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
