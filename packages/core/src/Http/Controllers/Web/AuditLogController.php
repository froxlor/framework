<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Resources\AuditLogs\AuditLogResource;
use Froxlor\Core\Support\Setting;
use Froxlor\UI\Support\UI;

class AuditLogController extends Controller
{
    public function index()
    {
        abort_unless(Setting::get('auditlog.enabled'), 403, 'Audit log is not enabled');

        return UI::render(AuditLogResource::class, 'index');
    }
}
