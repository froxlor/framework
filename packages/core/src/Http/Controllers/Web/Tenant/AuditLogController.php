<?php

namespace Froxlor\Core\Http\Controllers\Web\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Resources\Tenants\Relations\AuditLogs\AuditLogResource;
use Froxlor\UI\Support\UI;

class AuditLogController extends Controller
{
    public function index(Tenant $tenant)
    {
        return UI::render(AuditLogResource::class, 'index', [$tenant]);
    }
}
