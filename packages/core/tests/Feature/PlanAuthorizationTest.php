<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class PlanAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_global_plans(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_global_plans(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans')
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_global_plan(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $name = 'Policy Test Plan ' . str()->ulid();

        $planId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/plans', [
                'name' => $name,
                'type' => 'tenant',
                'description' => 'Created by PlanAuthorizationTest',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $planId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/plans/' . $planId, [
                'description' => 'Updated by PlanAuthorizationTest',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/plans/' . $planId)
            ->assertNoContent();
    }

    public function test_assigned_global_plan_cannot_be_deleted(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Platform Unlimited')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $originalPlanId = $tenant->plan_id;

        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/plans/' . $plan->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);

        $tenant->update(['plan_id' => $originalPlanId]);
    }

    public function test_assigned_global_plan_type_cannot_be_changed(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Platform Unlimited')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $originalPlanId = $tenant->plan_id;

        $tenant->update(['plan_id' => $plan->id]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/plans/' . $plan->id, [
                'type' => 'environment',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        $tenant->update(['plan_id' => $originalPlanId]);
    }

    public function test_plan_tenant_id_cannot_be_changed_through_update(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Tenant Starter')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/plans/' . $plan->id, [
                'tenant_id' => $tenant->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_super_admin_can_list_global_plan_users(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Platform Unlimited')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $originalPlanId = $tenant->plan_id;

        $tenant->update(['plan_id' => $plan->id]);

        $assignments = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $plan->id . '/users')
            ->assertOk()
            ->json('data'));

        $this->assertNotNull($assignments->firstWhere('tenant_id', $tenant->id));

        $tenant->update(['plan_id' => $originalPlanId]);
    }

    public function test_tenant_admin_cannot_list_global_plan_users(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->where('name', 'Platform Unlimited')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $plan->id . '/users')
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_manage_global_plan(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $plan = Plan::query()->whereNull('tenant_id')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/' . $plan->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/plans', [
                'name' => 'Forbidden Policy Test Plan ' . str()->ulid(),
                'type' => 'tenant',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/plans/' . $plan->id, [
                'description' => 'Forbidden update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/plans/' . $plan->id)
            ->assertForbidden();
    }
}
