<?php

namespace Froxlor\Core\Resources\Tenants\Relations\AuditLogs;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\AuditLogs\Tables\AuditLogTable;
use Froxlor\UI\Resources\Resource;
use Froxlor\UI\Tables\Table;

class AuditLogResource extends Resource
{
    public function index(Tenant $tenant): Table
    {
        return Table::make()
            ->title(trans('froxlor-core::generic.audit-log'))
            ->description(trans('froxlor-core::generic.show_resource_list', ['resource' => trans('froxlor-core::generic.audit-log')]))
            ->fetch(route('api.tenants.audit-log.index', ['tenant' => $tenant]))
            ->columns(AuditLogTable::columns())
            ->actions(AuditLogTable::actions());
    }
}
