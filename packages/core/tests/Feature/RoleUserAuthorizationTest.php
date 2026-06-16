<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class RoleUserAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_global_role_users(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Super-Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/users')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_global_role_users(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Super-Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/users')
            ->assertForbidden();
    }

    public function test_global_role_user_route_does_not_list_tenant_role_users(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Role User Policy Test ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id . '/users')
            ->assertForbidden();
    }
}
