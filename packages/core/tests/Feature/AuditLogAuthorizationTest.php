<?php

namespace Tests\Feature;

use Froxlor\Core\Models\User;
use Tests\TestCase;

class AuditLogAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_global_audit_log(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/audit-log')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_global_audit_log(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/audit-log')
            ->assertForbidden();
    }
}
