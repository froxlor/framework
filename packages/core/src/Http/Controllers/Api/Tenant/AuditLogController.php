<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant)
    {
        Gate::authorize('tenantViewAny', [AuditLog::class, $tenant]);

        $this->checkFeatureEnabled('auditlog.enabled', 'auditlog not enabled');

        return Response::jsonResourceCollection(
            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->with(['environment'])
                ->orderBy('created_at', 'desc')
        );
    }
}
