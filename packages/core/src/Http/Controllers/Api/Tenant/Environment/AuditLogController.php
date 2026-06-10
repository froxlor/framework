<?php

namespace Froxlor\Core\Http\Controllers\Api\Tenant\Environment;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant, Environment $environment)
    {
        Gate::authorize('tenantEnvViewAny', [AuditLog::class, $tenant, $environment]);

        $this->checkFeatureEnabled('auditlog.enabled', 'auditlog not enabled');

        return Response::jsonResourceCollection(
            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->where('environment_id', $environment->id)
                ->orderBy('created_at', 'desc')
        );
    }
}
