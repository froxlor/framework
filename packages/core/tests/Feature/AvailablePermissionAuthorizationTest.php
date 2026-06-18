<?php

namespace Tests\Feature;

use Froxlor\Core\Models\User;
use Tests\TestCase;

class AvailablePermissionAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_available_permissions_for_roles(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/permissions')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_available_permissions_for_roles(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/roles/permissions')
            ->assertForbidden();
    }

    public function test_permissions_are_not_crud_managed_over_api(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/permissions', [
                'key' => 'forbidden-permission-crud.' . str()->ulid(),
                'name' => 'Forbidden Permission CRUD',
            ])
            ->assertNotFound();
    }
}
