<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AuditLog::class);

        $this->checkFeatureEnabled('auditlog.enabled', 'auditlog not enabled');

        return Response::jsonResourceCollection(AuditLog::query()->with(['environment', 'tenant'])->orderBy('created_at', 'desc'));
    }
}
