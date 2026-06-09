<?php

namespace Froxlor\Core\Support;

use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;

class Audit
{
    /**
     * @param string $audit_content
     * @param Tenant|null $tenant
     * @param Environment|null $environment
     * @param array|null $context
     *
     * @return void
     */
    public static function log(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null): void
    {
        $auditable = request()->user();
        if (empty($auditable)) {
            $auditable = null;
        }
        self::log_internal($auditable, $audit_content, $tenant, $environment, $context);
    }

    /**
     * @param ?User $auditable
     * @param string $audit_content
     * @param Tenant|null $tenant
     * @param Environment|null $environment
     * @param array|null $context
     *
     * @return void
     * @internal
     */
    private static function log_internal(?User $auditable, string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null): void
    {
        if (Setting::get('auditlog.enabled')) {
            if (empty($context)) {
                $context = [];
            }
            $context['__ref'] = url()->previous();

            AuditLog::query()->createQuietly([
                'auditable_id' => $auditable?->getKey(),
                'auditable_type' => $auditable?->getMorphClass(),
                'tenant_id' => $tenant?->getKey(),
                'environment_id' => $environment?->getKey(),
                'action' => $audit_content,
                'context' => $context
            ]);
        }
    }
}
