<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
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
