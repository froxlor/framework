<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantEnvironmentAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_environment(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $environmentId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Tenant Environment Policy Test ' . str()->ulid(),
                'description' => 'Created by TenantEnvironmentAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environmentId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environmentId, [
                'description' => 'Updated by TenantEnvironmentAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $environmentId)
            ->assertNoContent();
    }

    public function test_unassigned_user_cannot_manage_environment(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Forbidden Tenant Environment ' . str()->ulid(),
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id)
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_manage_environment_from_another_tenant_route(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherEnvironment = Environment::query()
            ->where('tenant_id', '!=', $tenant->id)
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $otherEnvironment->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $otherEnvironment->id, [
                'description' => 'Forbidden cross-tenant update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $otherEnvironment->id)
            ->assertForbidden();
    }
}
