<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class TenantPlanAuthorizationTest extends TestCase
{
    public function test_tenant_admin_can_manage_tenant_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $planId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans', [
                'name' => 'Tenant Policy Test Plan ' . str()->ulid(),
                'type' => 'tenant',
                'description' => 'Created by TenantPlanAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans/' . $planId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/plans/' . $planId, [
                'description' => 'Updated by TenantPlanAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/plans/' . $planId)
            ->assertNoContent();
    }

    public function test_assigned_tenant_plan_cannot_be_deleted(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Assigned Tenant Plan ' . str()->ulid(),
            'type' => 'tenant',
        ]);
        $originalPlanId = $tenant->plan_id;

        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);

        $tenant->update(['plan_id' => $originalPlanId]);
    }

    public function test_unassigned_user_cannot_manage_tenant_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $user = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();
        $plan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Forbidden Tenant Policy Test Plan ' . str()->ulid(),
            'type' => 'tenant',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tenants/' . $tenant->id . '/plans', [
                'name' => 'Forbidden Tenant Plan ' . str()->ulid(),
                'type' => 'tenant',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/tenants/' . $tenant->id . '/plans/' . $plan->id)
            ->assertForbidden();
    }
}
