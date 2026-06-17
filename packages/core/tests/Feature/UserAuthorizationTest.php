<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Role;
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
}
