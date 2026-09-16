<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\AdministrationGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Own fixtures, actual policies, MariaDB transactions; no dependency on development seed users. */
class UserSecurityBoundariesTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;

    private Environment $environment;

    private Permission $wildcard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('s', 32))]);
        $this->wildcard = Permission::query()->firstOrCreate(['key' => '*'], ['name' => 'Everything']);
        // Isolate the administration invariant from any existing development administrators.
        // DatabaseTransactions rolls this and every fixture back after each test.
        DB::table('permission_role')->where('permission_id', $this->wildcard->id)->update(['inheritable' => false]);
        Model::withoutEvents(function (): void {
            $plan = Plan::query()->create(['name' => 'Security '.str()->ulid()]);
            $this->tenant = Tenant::query()->create(['name' => 'Security tenant', 'plan_id' => $plan->id]);
            $this->environment = Environment::query()->create([
                'name' => 'Security environment', 'tenant_id' => $this->tenant->id, 'plan_id' => $plan->id,
            ]);
        });
    }

    public function test_tenant_update_rejects_credentials_without_partial_changes(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $target = $this->tenantUser($role = $this->role(['tenants.index']));
        $before = $target->getRawOriginal();
        foreach ([['password' => 'replacement-password'], ['email' => 'changed@example.test'], ['password' => null]] as $credentials) {
            $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), [
                ...$credentials, 'role_id' => null, 'first_name' => 'Must not persist',
            ])->assertUnprocessable();
            $this->assertSame($before['email'], $target->fresh()->email);
            $this->assertSame($before['password'], $target->fresh()->getRawOriginal('password'));
            $this->assertSame($before['first_name'], $target->fresh()->first_name);
            $this->assertSame($role->id, $target->tenants()->first()->pivot->role_id);
        }
    }

    public function test_environment_update_rejects_credentials_even_for_global_admin(): void
    {
        $actor = $this->admin();
        $target = $this->environmentUser($this->role(['tenants.environments.index']));
        $before = $target->getRawOriginal('password');
        $this->actingAs($actor, 'sanctum')->patchJson($this->environmentPath($target), [
            'email' => 'changed@example.test', 'password' => 'replacement-password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertSame($before, $target->fresh()->getRawOriginal('password'));
    }

    public function test_scoped_admin_cannot_use_global_account_update_even_on_self(): void
    {
        $actor = $this->environmentUser($this->role(['tenants.environments.users.*']));
        $this->actingAs($actor, 'sanctum')->patchJson('/api/users/'.$actor->id, [
            'password' => 'replacement-password',
        ])->assertForbidden();
    }

    public function test_global_account_permission_still_allows_credentials_update(): void
    {
        $actor = $this->admin();
        $target = $this->tenantUser(null);
        $this->actingAs($actor, 'sanctum')->patchJson('/api/users/'.$target->id, [
            'email' => 'changed-'.str()->ulid().'@example.test', 'password' => 'replacement-password',
        ])->assertOk();
        $this->assertTrue(Hash::check('replacement-password', $target->fresh()->password));
    }

    public function test_null_tenant_role_revokes_permissions_but_preserves_membership_and_plan(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $target = $this->tenantUser($this->role(['tenants.index']));
        $target->tenants()->updateExistingPivot($this->tenant->id, ['plan_id' => $this->tenant->plan_id]);
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), ['role_id' => null])->assertOk();
        $pivot = $target->tenants()->firstOrFail()->pivot;
        $this->assertNull($pivot->role_id);
        $this->assertSame($this->tenant->plan_id, $pivot->plan_id);
        $this->assertFalse($pivot->hasPermission('tenants.index'));
        $this->assertFalse($pivot->canDelegatePermission('tenants.index'));
    }

    public function test_role_alias_can_revoke_and_omission_keeps_role(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $target = $this->tenantUser($role = $this->role(['tenants.index']));
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), ['first_name' => 'Updated'])->assertOk();
        $this->assertSame($role->id, $target->tenants()->first()->pivot->role_id);
        $this->patchJson($this->tenantPath($target), ['role' => null])->assertOk();
        $this->assertNull($target->tenants()->first()->pivot->role_id);
    }

    public function test_conflicting_aliases_are_rejected_including_null(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $target = $this->tenantUser($role = $this->role(['tenants.index']));
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), [
            'role_id' => null, 'role' => $role->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame($role->id, $target->tenants()->first()->pivot->role_id);
    }

    public function test_environment_role_can_be_revoked_without_removing_customer_membership(): void
    {
        $actor = $this->environmentUser($this->role(['tenants.environments.users.*']));
        $target = $this->environmentUser($this->role(['tenants.environments.index']));
        $this->actingAs($actor, 'sanctum')->patchJson($this->environmentPath($target), ['environment_role' => null])->assertOk();
        $pivot = $target->environments()->firstOrFail()->pivot;
        $this->assertNull($pivot->role_id);
        $this->assertFalse($pivot->hasPermission('tenants.environments.index'));
        $this->assertFalse($pivot->canDelegatePermission('tenants.environments.index'));
        $this->assertTrue($target->tenants()->whereKey($this->tenant->id)->exists());
    }

    public function test_environment_admin_cannot_revoke_tenant_role(): void
    {
        $actor = $this->environmentUser($this->role(['tenants.environments.users.*']));
        $target = $this->environmentUser($this->role([]));
        $role = $this->role(['tenants.users.*']);
        $target->tenants()->updateExistingPivot($this->tenant->id, ['role_id' => $role->id]);
        $this->actingAs($actor, 'sanctum')->patchJson($this->environmentPath($target), [
            'tenant_role' => null, 'environment_role' => null,
        ])->assertForbidden();
        $this->assertSame($role->id, $target->tenants()->first()->pivot->role_id);
        $this->assertNotNull($target->environments()->first()->pivot->role_id);
    }

    public function test_environment_route_rejects_global_assignment_fields(): void
    {
        $actor = $this->admin();
        $target = $this->environmentUser($this->role([]));
        $this->actingAs($actor, 'sanctum')->patchJson($this->environmentPath($target), ['role_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_tenant_admin_can_revoke_tenant_role_through_environment_route(): void
    {
        $actor = $this->admin();
        $target = $this->environmentUser($this->role([]));
        $target->tenants()->updateExistingPivot($this->tenant->id, ['role_id' => $this->role(['tenants.index'])->id]);
        $this->actingAs($actor, 'sanctum')->patchJson($this->environmentPath($target), ['tenant_role' => null])->assertOk();
        $this->assertNull($target->tenants()->first()->pivot->role_id);
    }

    public function test_foreign_user_cannot_revoke_scoped_role(): void
    {
        $actor = $this->user();
        $target = $this->tenantUser($this->role(['tenants.index']));
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), ['role_id' => null])->assertForbidden();
    }

    public function test_non_null_role_assignment_still_requires_delegation(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $target = $this->tenantUser(null);
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($target), ['role_id' => $this->role(['*'])->id])
            ->assertUnprocessable()->assertJsonValidationErrors('role_id');
        $this->assertNull($target->tenants()->first()->pivot->role_id);
        $role = $this->role(['tenants.users.index']);
        $this->patchJson($this->tenantPath($target), ['role_id' => $role->id])->assertOk();
        $this->assertSame($role->id, $target->tenants()->first()->pivot->role_id);
    }

    public function test_guard_preserves_root_status_and_allows_normal_tenant_update(): void
    {
        $actor = $this->admin();
        $this->actingAs($actor, 'sanctum')->patchJson('/api/tenants/'.$this->tenant->id, ['name' => 'Updated root'])->assertOk();
        $other = Tenant::query()->create(['name' => 'Another root', 'plan_id' => $this->tenant->plan_id]);
        try {
            AdministrationGuard::run(fn () => $this->tenant->update(['parent_tenant_id' => $other->id]));
            $this->fail('Expected root administration protection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('administration', $exception->errors());
        }
        $this->assertNull($this->tenant->fresh()->parent_tenant_id);
    }

    public function test_last_global_administrator_cannot_be_deleted(): void
    {
        $actor = $this->admin();
        $this->actingAs($actor, 'sanctum')->deleteJson('/api/users/'.$actor->id)
            ->assertUnprocessable()->assertJsonValidationErrors('administration');
        $this->assertNull($actor->fresh()->deleted_at);
    }

    public function test_last_global_permission_and_delegation_cannot_be_removed(): void
    {
        $actor = $this->admin();
        $role = $actor->roles()->firstOrFail();
        $path = '/api/roles/'.$role->id.'/permissions';
        $this->actingAs($actor, 'sanctum')->deleteJson($path.'/'.$this->wildcard->id)
            ->assertUnprocessable()->assertJsonValidationErrors('administration');
        foreach ([['inheritable' => false], []] as $options) {
            $this->postJson($path, ['permission_id' => $this->wildcard->id, ...$options])
                ->assertUnprocessable()->assertJsonValidationErrors('administration');
        }
        $this->assertTrue($actor->canDelegatePermission('*'));
    }

    public function test_two_users_sharing_the_last_admin_role_do_not_make_its_removal_safe(): void
    {
        $actor = $this->admin();
        $other = $this->tenantUser(null);
        $role = $actor->roles()->first();
        $other->roles()->attach($role);
        $this->actingAs($actor, 'sanctum')->deleteJson('/api/roles/'.$role->id.'/permissions/'.$this->wildcard->id)
            ->assertUnprocessable();
    }

    public function test_self_demotion_is_allowed_when_independent_admin_remains(): void
    {
        $actor = $this->admin();
        $other = $this->admin();
        $role = $actor->roles()->first();
        $this->actingAs($actor, 'sanctum')->deleteJson('/api/roles/'.$role->id.'/permissions/'.$this->wildcard->id)->assertOk();
        $this->assertFalse($actor->hasPermission('*'));
        $this->assertTrue($other->canDelegatePermission('*'));
    }

    public function test_self_deletion_is_allowed_when_another_admin_remains(): void
    {
        $actor = $this->admin();
        $other = $this->admin();
        $this->actingAs($actor, 'sanctum')->deleteJson('/api/users/'.$actor->id)->assertNoContent();
        $this->assertNull(User::query()->find($actor->id));
        $this->assertTrue($other->canDelegatePermission('*'));
    }

    public function test_soft_deleted_or_scoped_admins_are_not_global_fallbacks(): void
    {
        $actor = $this->admin();
        $deleted = $this->admin();
        $deleted->delete();
        $this->tenantUser($this->role(['*']));
        $this->actingAs($actor, 'sanctum')->deleteJson('/api/users/'.$actor->id)->assertUnprocessable();
    }

    public function test_last_root_admin_membership_cannot_be_detached(): void
    {
        $actor = $this->admin();
        $this->actingAs($actor, 'sanctum')->deleteJson($this->tenantPath($actor))
            ->assertUnprocessable()->assertJsonValidationErrors('administration');
        $this->assertTrue($actor->tenants()->whereKey($this->tenant->id)->exists());
    }

    public function test_root_membership_can_be_detached_when_another_root_admin_remains(): void
    {
        $actor = $this->admin();
        $this->admin();
        $this->actingAs($actor, 'sanctum')->deleteJson($this->tenantPath($actor))->assertOk();
        $this->assertFalse($actor->tenants()->whereKey($this->tenant->id)->exists());
    }

    public function test_guard_rolls_back_extension_global_role_revocation(): void
    {
        $actor = $this->admin();
        try {
            AdministrationGuard::run(fn () => $actor->roles()->detach());
            $this->fail('Expected last administrator protection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('administration', $exception->errors());
        }
        $this->assertTrue($actor->canDelegatePermission('*'));
    }

    public function test_guard_holds_database_mutex_against_a_second_connection(): void
    {
        $name = 'administration-lock-test';
        config(['database.connections.'.$name => config('database.connections.'.DB::getDefaultConnection())]);
        $other = DB::connection($name);
        try {
            $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
            // A shared read must succeed before the guard, even with fixture FK locks.
            $other->beginTransaction();
            $this->assertNotNull($other->table('permissions')->where('key', '*')->sharedLock()->first());
            $other->rollBack();
            AdministrationGuard::run(function () use ($other): void {
                $other->beginTransaction();
                try {
                    $other->table('permissions')->where('key', '*')->sharedLock()->first();
                    $this->fail('A concurrent security mutation acquired the administration mutex.');
                } catch (QueryException $exception) {
                    $this->assertSame(1205, $exception->errorInfo[1]);
                } finally {
                    $other->rollBack();
                }
            });
        } finally {
            DB::purge($name);
        }
    }

    public function test_sequential_demotions_cannot_remove_both_independent_admins(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $this->actingAs($first, 'sanctum')->deleteJson('/api/users/'.$first->id)->assertNoContent();
        $this->actingAs($second, 'sanctum')->deleteJson('/api/users/'.$second->id)
            ->assertUnprocessable()->assertJsonValidationErrors('administration');
        $this->assertNotNull($second->fresh());
    }

    public function test_scoped_user_can_revoke_own_role_without_losing_membership(): void
    {
        $actor = $this->tenantUser($this->role(['tenants.users.*']));
        $this->actingAs($actor, 'sanctum')->patchJson($this->tenantPath($actor), ['role_id' => null])->assertOk();
        $this->assertTrue($actor->tenants()->whereKey($this->tenant->id)->exists());
        $this->patchJson($this->tenantPath($actor), ['first_name' => 'Denied'])->assertForbidden();
    }

    private function role(array $keys): Role
    {
        $role = Role::query()->create(['name' => 'Security role '.str()->ulid()]);
        foreach ($keys as $key) {
            $permission = Permission::query()->firstOrCreate(['key' => $key], ['name' => $key]);
            $role->permissions()->attach($permission, ['inheritable' => true]);
        }

        return $role;
    }

    private function tenantUser(?Role $role): User
    {
        return Model::withoutEvents(function () use ($role): User {
            $user = $this->user();
            $user->tenants()->attach($this->tenant, ['role_id' => $role?->id]);

            return $user;
        });
    }

    private function environmentUser(Role $role): User
    {
        $user = $this->tenantUser(null);
        $user->environments()->attach($this->environment, ['role_id' => $role->id]);

        return $user;
    }

    private function user(): User
    {
        return User::query()->create([
            'first_name' => 'Security', 'last_name' => 'Test',
            'email' => 'security-'.str()->ulid().'@example.test',
            'password' => 'original-password',
        ]);
    }

    private function admin(): User
    {
        $user = $this->tenantUser(null);
        $user->roles()->attach($this->role(['*']));

        return $user;
    }

    private function tenantPath(User $user): string
    {
        return '/api/tenants/'.$this->tenant->id.'/users/'.$user->id;
    }

    private function environmentPath(User $user): string
    {
        return '/api/tenants/'.$this->tenant->id.'/environments/'.$this->environment->id.'/users/'.$user->id;
    }
}
