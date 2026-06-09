<?php

namespace Froxlor\Core\Resources\AuditLogs;

use Froxlor\Core\Resources\AuditLogs\Tables\AuditLogTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Tables\Table;

class AuditLogResource extends Resource
{
    public function index(): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.audit-logs'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.audit-logs')]))
            ->fetch(route('api.audit-log.index'))
            ->columns(AuditLogTable::columns());
    }
}
