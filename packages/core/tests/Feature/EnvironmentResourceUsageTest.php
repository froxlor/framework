<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\AuditLog;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class EnvironmentResourceUsageTest extends TestCase
{
    public function test_tenant_environment_creation_records_resource_usage(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Environment::getResourceKey())->delete();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

        $environmentId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Usage Test Environment ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('tenant_usage', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'resource_key' => Environment::getResourceKey(),
            'resource_id' => $environmentId,
        ]);
    }

    public function test_tenant_environment_creation_respects_plan_resource_limit(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $resource = Resource::query()->where('key', Environment::getResourceKey())->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Environment::getResourceKey())->delete();
        $plan = Plan::query()->create([
            'name' => 'Single Environment Limit ' . str()->ulid(),
        ]);
        $plan->resources()->attach($resource, ['limit' => 1]);
        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Allowed Environment ' . str()->ulid(),
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Rejected Environment ' . str()->ulid(),
            ])
            ->assertStatus(500);
    }

    public function test_parent_tenant_user_creating_environment_for_subtenant_counts_usage_on_both_tenants(): void
    {
        $parentTenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $subTenant = Tenant::query()->where('name', 'Kunde #2')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $parentTenant->tenantUsages()->where('resource_key', Environment::getResourceKey())->delete();
        $subTenant->tenantUsages()->where('resource_key', Environment::getResourceKey())->delete();
        $parentTenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);
        $subTenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

        $this->actingAs($user, 'sanctum');

        $environment = Environment::query()->create([
            'tenant_id' => $subTenant->id,
            'name' => 'Subtenant Usage Test Environment ' . str()->ulid(),
        ]);

        $this->assertDatabaseHas('tenant_usage', [
            'tenant_id' => $parentTenant->id,
            'user_id' => $user->id,
            'resource_key' => Environment::getResourceKey(),
            'resource_id' => $environment->id,
        ]);
        $this->assertDatabaseHas('tenant_usage', [
            'tenant_id' => $subTenant->id,
            'user_id' => $user->id,
            'resource_key' => Environment::getResourceKey(),
            'resource_id' => $environment->id,
        ]);
    }

    public function test_tenant_environment_actions_write_audit_log_with_tenant_and_environment_context(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenant->tenantUsages()->where('resource_key', Environment::getResourceKey())->delete();
        $tenant->update(['plan_id' => Plan::query()->where('name', 'Test Tenant Unlimited')->firstOrFail()->id]);

        $environmentId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments', [
                'name' => 'Audited Environment ' . str()->ulid(),
            ])
            ->assertCreated()
            ->json('data.id');

        $environment = Environment::query()->findOrFail($environmentId);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => $environment->id,
            'action' => 'environment "' . $environment->name . '" created',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id, [
                'name' => 'Audited Environment Updated',
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => $environment->id,
            'action' => 'environment "Audited Environment Updated" updated',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id)
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
            'environment_id' => $environment->id,
            'action' => 'environment "Audited Environment Updated" deleted',
        ]);

        $deleteLog = AuditLog::query()
            ->where('action', 'environment "Audited Environment Updated" deleted')
            ->latest()
            ->firstOrFail();

        $this->assertSame($environment->plan_id, $deleteLog->context['plan_id']);
    }
}
