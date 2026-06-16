<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantUserAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();

        $userId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'User',
                'email' => 'tenant-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users/' . $userId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $userId, [
                'first_name' => 'Updated',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/users/' . $userId)
            ->assertOk();
    }

    public function test_tenant_admin_can_assign_tenant_owned_role_to_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User Assignment Role ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Tenant',
                'last_name' => 'Role User',
                'email' => 'tenant-role-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertCreated();
    }

    public function test_tenant_admin_cannot_assign_role_from_another_tenant(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Tenant User Assignment Role ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Foreign Role User',
                'email' => 'forbidden-foreign-role-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id, [
                'role_id' => $role->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_unassigned_user_cannot_manage_tenant_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Tenant User',
                'email' => 'forbidden-tenant-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id, [
                'first_name' => 'Forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id)
            ->assertForbidden();
    }
}
