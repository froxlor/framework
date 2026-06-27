<?php

namespace Froxlor\Core\Support;

use BadMethodCallException;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;

/**
 * @method static void debug(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void info(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void notice(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void warning(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void error(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void critical(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void alert(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 * @method static void emergency(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null) Log debug message
 */
class Audit
{
    private const array LEVELS = [
        'debug' => 7,
        'info' => 6,
        'notice' => 5,
        'warning' => 4,
        'error' => 3,
        'critical' => 2,
        'alert' => 1,
        'emergency' => 0,
    ];

    public static function __callStatic(string $method, array $args): void
    {
        if (!isset(self::LEVELS[$method])) {
            throw new BadMethodCallException();
        }

        self::log(
            $args[0],
            $args[1] ?? null,
            $args[2] ?? null,
            $args[3] ?? null,
            self::LEVELS[$method]
        );
    }

    /**
     * @param string $audit_content
     * @param Tenant|null $tenant
     * @param Environment|null $environment
     * @param array|null $context
     * @param int|null $severity
     * @return void
     */
    public static function log(string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null, ?int $severity = 5): void
    {
        $auditable = request()->user() ?? auth()->user();
        if (empty($auditable)) {
            $auditable = null;
        }
        self::log_internal($auditable, $audit_content, $tenant, $environment, $context, $severity);
    }

    /**
     * @param ?User $auditable
     * @param string $audit_content
     * @param Tenant|null $tenant
     * @param Environment|null $environment
     * @param array|null $context
     * @param int|null $severity
     * @return void
     * @internal
     */
    private static function log_internal(?User $auditable, string $audit_content, ?Tenant $tenant = null, ?Environment $environment = null, ?array $context = null, ?int $severity = 5): void
    {
        if (Setting::get('auditlog.enabled') && Setting::get('auditlog.severity') >= $severity) {
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
