<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_global_roles(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_global_roles(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles')
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_global_role(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $name = 'Policy Test Role ' . str()->ulid();

        $roleId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles', [
                'name' => $name,
                'description' => 'Created by RoleAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $roleId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/roles/' . $roleId, [
                'description' => 'Updated by RoleAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $roleId)
            ->assertNoContent();
    }

    public function test_global_role_names_must_be_unique(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles', [
                'name' => $role->name,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_global_role_create_can_create_tenant_role_when_authorized_for_tenant(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $name = 'Global Route Tenant Role ' . str()->ulid();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles', [
                'tenant_id' => $tenant->id,
                'name' => $name,
            ])
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id);
    }

    public function test_assigned_global_role_cannot_be_deleted(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->where('name', 'Super-Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_tenant_admin_cannot_manage_global_role(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/' . $role->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/roles', [
                'name' => 'Forbidden Policy Test Role ' . str()->ulid(),
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/roles/' . $role->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/roles/' . $role->id)
            ->assertForbidden();
    }
}
