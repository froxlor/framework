<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\Fakes\FakeNodeAdapter;
use Tests\TestCase;

class TenantEnvironmentAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/Fakes/FakeNodeAdapter.php';

        if (!in_array(FakeNodeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(FakeNodeAdapter::class);
        }

        Tenant::query()
            ->where('name', 'First customer')
            ->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);
    }

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

    public function test_tenant_admin_cannot_create_environment_on_unavailable_node(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $node = Node::query()->create([
            'tenant_id' => $otherTenant->id,
            'adapter' => FakeNodeAdapter::class,
            'name' => 'Foreign Environment Node ' . str()->ulid(),
            'hostname' => 'foreign-environment-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Rejected Foreign Node Environment ' . str()->ulid(),
                'node_id' => $node->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['node_id']);
    }

    public function test_tenant_admin_cannot_update_environment_to_unavailable_node(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $node = Node::query()->create([
            'tenant_id' => $otherTenant->id,
            'adapter' => FakeNodeAdapter::class,
            'name' => 'Foreign Update Environment Node ' . str()->ulid(),
            'hostname' => 'foreign-update-environment-node.local',
            'username' => 'root',
            'sudo' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id, [
                'node_id' => $node->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['node_id']);
    }

    public function test_tenant_admin_cannot_assign_foreign_environment_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $foreignPlan = Plan::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Environment Plan ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Rejected Foreign Plan Environment ' . str()->ulid(),
                'plan_id' => $foreignPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_tenant_admin_cannot_update_environment_to_foreign_environment_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $foreignPlan = Plan::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Update Environment Plan ' . str()->ulid(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id, [
                'plan_id' => $foreignPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_tenant_admin_can_assign_available_environment_plan_on_create_and_update(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenantPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Available Environment Plan ' . str()->ulid(),
        ]);
        $globalPlan = Plan::query()->create([
            'tenant_id' => null,
            'name' => 'Global Available Environment Plan ' . str()->ulid(),
        ]);

        $environmentId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Accepted Environment Plan ' . str()->ulid(),
                'plan_id' => $tenantPlan->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.plan_id', $tenantPlan->id)
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environmentId, [
                'plan_id' => $globalPlan->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.plan_id', $globalPlan->id);
    }
}
