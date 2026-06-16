<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantEnvironmentUserAuthorizationTest extends TestCase
{
    public function test_environment_admin_can_manage_environment_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/users';

        $userId = $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Environment',
                'last_name' => 'User',
                'email' => 'environment-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $role->id,
                'environment_role' => $role->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath . '/' . $userId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson($basePath . '/' . $userId, [
                'first_name' => 'Updated',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $userId)
            ->assertNoContent();
    }

    public function test_unassigned_user_cannot_manage_environment_user(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $role = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/users';

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath . '/' . $targetUser->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Forbidden',
                'last_name' => 'Environment User',
                'email' => 'forbidden-environment-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $role->id,
                'environment_role' => $role->id,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson($basePath . '/' . $targetUser->id, [
                'first_name' => 'Forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $targetUser->id)
            ->assertForbidden();
    }
}
