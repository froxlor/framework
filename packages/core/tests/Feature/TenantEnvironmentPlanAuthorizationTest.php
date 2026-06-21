<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantEnvironmentPlanAuthorizationTest extends TestCase
{
    public function test_environment_admin_can_manage_environment_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $planId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans', [
                'name' => 'Environment Policy Test Plan ' . str()->ulid(),
                'description' => 'Created by TenantEnvironmentPlanAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans/' . $planId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans/' . $planId, [
                'description' => 'Updated by TenantEnvironmentPlanAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans/' . $planId)
            ->assertNoContent();
    }

    public function test_unassigned_user_cannot_manage_environment_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Forbidden Environment Policy Test Plan ' . str()->ulid(),
            'type' => 'environment',
        ]);

        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans';

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson($basePath . '/' . $plan->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'name' => 'Forbidden Environment Plan ' . str()->ulid(),
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson($basePath . '/' . $plan->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson($basePath . '/' . $plan->id)
            ->assertForbidden();
    }

    public function test_assigned_environment_plan_cannot_be_deleted(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Assigned Environment Plan ' . str()->ulid(),
            'type' => 'environment',
        ]);
        $originalPlanId = $environment->plan_id;

        $environment->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/plans/' . $plan->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);

        $environment->update(['plan_id' => $originalPlanId]);
    }
}
