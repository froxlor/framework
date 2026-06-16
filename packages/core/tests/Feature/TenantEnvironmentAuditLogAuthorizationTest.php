<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantEnvironmentAuditLogAuthorizationTest extends TestCase
{
    public function test_environment_admin_can_list_environment_audit_log(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/audit-log')
            ->assertOk();
    }

    public function test_unassigned_user_cannot_list_environment_audit_log(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/audit-log')
            ->assertForbidden();
    }
}
