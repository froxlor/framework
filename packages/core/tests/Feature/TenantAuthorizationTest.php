<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_view_own_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id)
            ->assertOk();
    }

    public function test_tenant_admin_show_unknown_tenant(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . random_int(10000, 99999))
            ->assertNotFound();
    }

    public function test_user_cannot_view_unassigned_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id)
            ->assertForbidden();
    }
}
