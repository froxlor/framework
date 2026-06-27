<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    public function test_super_admin_can_list_global_users(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_global_users(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_global_user(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->where('name', 'Super-Admin')->firstOrFail();

        $userId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'Policy',
                'last_name' => 'User',
                'email' => 'global-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/' . $userId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $userId, [
                'first_name' => 'Updated',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/users/' . $userId)
            ->assertNoContent();
    }

    public function test_user_update_email_must_be_unique_except_current_user(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $targetUser = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $otherUser = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $targetUser->id, [
                'email' => $targetUser->email,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $targetUser->id, [
                'email' => $otherUser->email,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_tenant_admin_cannot_manage_global_user(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $targetUser = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/' . $targetUser->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'User',
                'email' => 'forbidden-global-user-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'role_id' => $role->id,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $targetUser->id, [
                'first_name' => 'Forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/users/' . $targetUser->id)
            ->assertForbidden();
    }

    public function test_global_user_create_checks_assigned_role_delegation(): void
    {
        $user = User::query()->create([
            'first_name' => 'Limited',
            'last_name' => 'Global User Manager',
            'email' => 'limited-global-user-manager-' . str()->ulid() . '@froxlor.test',
            'password' => bcrypt('secret-password'),
        ]);
        $role = Role::query()->create([
            'name' => 'Limited Global User Manager ' . str()->ulid(),
        ]);
        $wildcardPermission = \Froxlor\Core\Models\Permission::query()->where('key', '*')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'Super-Admin')->firstOrFail();
        $tenant = \Froxlor\Core\Models\Tenant::query()->where('name', 'First customer')->firstOrFail();

        $role->permissions()->attach($wildcardPermission, ['inheritable' => false]);
        $user->roles()->attach($role);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Global Assignment',
                'email' => 'forbidden-global-assignment-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_id' => $tenant->id,
                'role_id' => $superAdminRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_global_user_create_checks_assigned_plan_limits(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $role = Role::query()->where('name', 'Admin')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'First customer')->firstOrFail();
        $resource = Resource::query()->where('type', 'tenant')->where('key', 'users')->firstOrFail();
        $parentPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Global User Parent Plan ' . str()->ulid(),
        ]);
        $parentPlan->resources()->attach($resource, ['limit' => 1]);
        $tenant->update(['plan_id' => $parentPlan->id]);
        $oversizedPlan = Plan::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Global User Oversized Plan ' . str()->ulid(),
        ]);
        $oversizedPlan->resources()->attach($resource, ['limit' => 2]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'Forbidden',
                'last_name' => 'Plan Assignment',
                'email' => 'forbidden-global-plan-assignment-' . str()->ulid() . '@froxlor.test',
                'password' => 'secret-password',
                'tenant_id' => $tenant->id,
                'role_id' => $role->id,
                'plan_id' => $oversizedPlan->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }
}
