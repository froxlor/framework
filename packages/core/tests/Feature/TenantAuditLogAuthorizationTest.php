<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantAuditLogAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_list_tenant_audit_log(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/audit-log')
            ->assertOk();
    }

    public function test_unassigned_user_cannot_list_tenant_audit_log(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/audit-log')
            ->assertForbidden();
    }
}
