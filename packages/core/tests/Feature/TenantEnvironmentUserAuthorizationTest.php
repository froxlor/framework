<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
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
        $tenantRole = Role::query()->where('name', 'Admin')->firstOrFail();
        $environmentRole = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/users';

        $userId = $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Environment',
                'last_name' => 'User',
                'email' => 'environment-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $tenantRole->id,
                'environment_role' => $environmentRole->id,
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
        $role = Role::query()->where('name', 'Admin')->firstOrFail();
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

    public function test_environment_admin_cannot_assign_tenant_role_without_delegation(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/users';

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Forbidden',
                'last_name' => 'Tenant Role',
                'email' => 'forbidden-env-tenant-role-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $superAdminRole->id,
                'environment_role' => $superAdminRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_role']);
    }

    public function test_environment_user_plan_must_be_environment_scope_and_within_environment_plan(): void
    {
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $environment = Environment::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Kunden Environment')
            ->firstOrFail();
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $tenantRole = Role::query()->where('name', 'Admin')->firstOrFail();
        $environmentRole = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $resource = Resource::query()->where('key', 'users')->where('type', 'environment')->firstOrFail();
        $basePath = '/api/tenants/' . $tenant->id . '/environments/' . $environment->id . '/users';

        $parentPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Environment Parent User Plan ' . str()->ulid(),
        ]);
        $parentPlan->resources()->attach($resource, ['limit' => 2]);
        $environment->update(['plan_id' => $parentPlan->id]);

        $validPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Environment Child User Plan ' . str()->ulid(),
        ]);
        $validPlan->resources()->attach($resource, ['limit' => 1]);

        $tooLargePlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Environment Too Large User Plan ' . str()->ulid(),
        ]);
        $tooLargePlan->resources()->attach($resource, ['limit' => 3]);
        $foreignPlan = Plan::query()->create([
            'tenant_id' => Tenant::query()->where('name', 'Kunde #2')->firstOrFail()->id,
            'name' => 'Foreign Environment User Plan ' . str()->ulid(),
        ]);
        $foreignPlan->resources()->attach($resource, ['limit' => 1]);

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Environment',
                'last_name' => 'Plan User',
                'email' => 'environment-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $tenantRole->id,
                'environment_role' => $environmentRole->id,
                'environment_plan' => $validPlan->id,
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Forbidden',
                'last_name' => 'Large Environment Plan User',
                'email' => 'environment-large-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $tenantRole->id,
                'environment_role' => $environmentRole->id,
                'environment_plan' => $tooLargePlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['environment_plan']);

        $this->actingAs($user, 'sanctum')
            ->postJson($basePath, [
                'first_name' => 'Forbidden',
                'last_name' => 'Foreign Environment Plan User',
                'email' => 'environment-foreign-plan-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_role' => $tenantRole->id,
                'environment_role' => $environmentRole->id,
                'environment_plan' => $foreignPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['environment_plan']);
    }
}
