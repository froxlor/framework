<?php

namespace Tests\Feature;

use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource as Definition;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Quota;
use Froxlor\Core\Support\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Own transactional fixtures; never reset or depend on named development seed records. */
class QuotaIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('q', 32))]);
    }

    public function test_tenant_limit_is_shared_by_users(): void
    {
        $tenant = $this->tenant(['tenant:users' => 1]);
        $a = $this->member($tenant);
        $b = $this->member($tenant);
        Resource::addUsage($tenant, $this->user(), $a);
        $this->assertFalse(Resource::hasUsageAvailable($tenant, User::class, $b));
        $this->expectException(ResourceLimitException::class);
        Resource::addUsage($tenant, $this->user(), $b);
    }

    public function test_personal_limit_cannot_replace_total_and_can_restrict_it(): void
    {
        $tenant = $this->tenant(['tenant:users' => 1]);
        $a = $this->member($tenant, $this->plan(['tenant:users' => -1]));
        Resource::addUsage($tenant, $this->user(), $a);
        $this->assertFalse(Resource::hasUsageAvailable($tenant, 'users', $a));
        $tenant = $this->tenant(['tenant:users' => -1]);
        $b = $this->member($tenant, $this->plan(['tenant:users' => 0]));
        $this->assertFalse(Resource::hasUsageAvailable($tenant, 'users', $b));
    }

    public function test_missing_resource_and_zero_deny_usage(): void
    {
        foreach ([[], ['tenant:users' => 0]] as $limits) {
            $tenant = $this->tenant($limits);
            $this->assertFalse(Resource::hasUsageAvailable($tenant, 'users', $this->member($tenant)));
        }
    }

    public function test_environment_users_share_environment_and_customer_budgets(): void
    {
        $tenant = $this->tenant(['environment:users' => 2]);
        $environment = $this->environment($tenant, $this->plan(['environment:users' => 1]));
        $a = $this->environmentMember($environment);
        $b = $this->environmentMember($environment);
        Resource::addEnvironmentUsage($environment, $this->user(), $a);
        $this->assertFalse($environment->userHasResourceAvailable($b, 'users'));
        $second = $this->environment($tenant);
        $c = $this->environmentMember($second);
        Resource::addEnvironmentUsage($second, $this->user(), $c);
        $this->assertFalse($second->userHasResourceAvailable($c, 'users'));
        $this->assertSame(2, Quota::tenantUsed($tenant->id, 'users', 'environment'));
    }

    public function test_environment_and_tenant_resource_keys_do_not_collide(): void
    {
        $tenant = $this->tenant(['tenant:users' => 0, 'environment:users' => 1]);
        $environment = $this->environment($tenant);
        $actor = $this->environmentMember($environment);
        $this->assertTrue($environment->userHasResourceAvailable($actor, 'users'));
        $this->assertFalse(Resource::hasUsageAvailable($tenant, 'users', $actor));
    }

    public function test_tenant_user_environment_limit_is_shared_across_environments(): void
    {
        $tenant = $this->tenant(['environment:users' => 10]);
        $actor = $this->member($tenant, $this->plan(['environment:users' => 1]));
        $a = $this->environment($tenant);
        $b = $this->environment($tenant);
        $this->attachEnvironment($a, $actor);
        $this->attachEnvironment($b, $actor);
        Resource::addEnvironmentUsage($a, $this->user(), $actor);
        $this->assertFalse($b->userHasResourceAvailable($actor, 'users'));
    }

    public function test_usage_booking_and_removal_are_idempotent_without_observer_recursion(): void
    {
        $tenant = $this->tenant(['tenant:users' => -1, 'environment:users' => -1]);
        $environment = $this->environment($tenant);
        $actor = $this->environmentMember($environment);
        $target = $this->user();
        $one = Resource::addEnvironmentUsage($environment, $target, $actor);
        $two = Resource::addEnvironmentUsage($environment, $target, $actor);
        $this->assertSame($one->id, $two->id);
        $this->assertSame($target->id, $one->resource->id);
        $this->assertSame(1, Resource::getEnvironmentUsage($environment, 'users'));
        Resource::removeEnvironmentUsage($environment, $target);
        Resource::removeEnvironmentUsage($environment, $target);
        $this->assertSame(0, Resource::getEnvironmentUsage($environment, 'users'));
        $one = Resource::addUsage($tenant, $target, $actor);
        $this->assertSame($one->id, Resource::addUsage($tenant, $target, $actor)->id);
        $this->assertSame($target->id, $one->resource->id);
        Resource::removeUsage($tenant, $target);
        Resource::removeUsage($tenant, $target);
        $this->assertSame(0, Resource::getUsage($tenant, 'users'));
    }

    public function test_same_user_membership_in_two_environments_counts_twice_for_customer(): void
    {
        $tenant = $this->tenant(['environment:users' => 2]);
        $a = $this->environment($tenant);
        $b = $this->environment($tenant);
        $actor = $this->environmentMember($a);
        $this->attachEnvironment($b, $actor);
        $target = $this->user();
        Resource::addEnvironmentUsage($a, $target, $actor);
        Resource::addEnvironmentUsage($b, $target, $actor);
        $this->assertSame(2, Quota::tenantUsed($tenant->id, 'users', 'environment'));
    }

    public function test_reservations_reduce_parent_capacity_but_child_usage_is_not_double_charged(): void
    {
        $parent = $this->tenant(['tenant:environments' => 2]);
        $actor = $this->member($parent);
        $plan = $this->plan(['tenant:environments' => 2], $parent);
        $child = Model::withoutEvents(fn () => Tenant::query()->create([
            'name' => 'Quota child', 'parent_tenant_id' => $parent->id, 'plan_id' => $plan->id,
        ]));
        Quota::transaction(fn () => PlanAssignments::syncTenantReservations($parent, $child, $plan));
        $this->assertFalse(Resource::hasUsageAvailable($parent, Environment::class, $actor));
        $this->actingAs($actor);
        $environment = Environment::query()->create(['tenant_id' => $child->id, 'name' => 'Owned by child']);
        $this->assertSame(0, Resource::getUsage($parent, Environment::class));
        $this->assertSame(1, Resource::getUsage($child, Environment::class));
        $this->assertSame(0, PlanAssignments::availableTenantBudget($parent)['tenant:environments']);
        $this->assertDatabaseHas('tenant_usage', ['tenant_id' => $child->id, 'resource_id' => $environment->id]);
    }

    public function test_multi_child_plan_growth_is_validated_as_a_single_reservation_change(): void
    {
        $parent = $this->tenant(['tenant:users' => 10]);
        $plan = $this->plan(['tenant:users' => 4], $parent);
        foreach ([1, 2] as $i) {
            $child = Model::withoutEvents(fn () => Tenant::query()->create([
                'name' => 'Child '.$i, 'plan_id' => $plan->id, 'parent_tenant_id' => $parent->id,
            ]));
            Quota::transaction(fn () => PlanAssignments::syncTenantReservations($parent, $child, $plan));
        }
        $this->rejects(fn () => PlanAssignments::updatePlanResourceLimit($plan, $this->definition('tenant:users'), 6, $parent));
        $this->assertSame(4, Quota::limit($plan->id, 'users', 'tenant'));
        $this->assertSame(8, (int) DB::table('tenant_resource_reservations')->where('tenant_id', $parent->id)->sum('limit'));
        PlanAssignments::updatePlanResourceLimit($plan, $this->definition('tenant:users'), 5, $parent);
        $this->assertSame(10, (int) DB::table('tenant_resource_reservations')->where('tenant_id', $parent->id)->sum('limit'));
    }

    public function test_parent_downgrade_cannot_leave_explicit_user_or_environment_plans_above_it(): void
    {
        $tenant = $this->tenant(['tenant:users' => 10, 'environment:users' => 10]);
        $this->member($tenant, $this->plan(['tenant:users' => 8]));
        $this->environment($tenant, $this->plan(['environment:users' => 8]));
        foreach (['tenant:users', 'environment:users'] as $key) {
            $this->rejects(fn () => PlanAssignments::updatePlanResourceLimit($tenant->plan, $this->definition($key), 5));
        }
        $this->assertSame(10, Quota::limit($tenant->plan_id, 'users', 'environment'));
    }

    public function test_environment_plan_downgrade_and_switch_validate_explicit_member_plans(): void
    {
        $tenant = $this->tenant(['environment:users' => 10]);
        $plan = $this->plan(['environment:users' => 10]);
        $environment = $this->environment($tenant, $plan);
        $member = $this->environmentMember($environment);
        Model::withoutEvents(fn () => $member->environments()->updateExistingPivot($environment->id, ['plan_id' => $this->plan(['environment:users' => 8])->id]));
        $this->rejects(fn () => PlanAssignments::updatePlanResourceLimit($plan, $this->definition('environment:users'), 5));
        $smaller = $this->plan(['environment:users' => 5]);
        $this->rejects(fn () => $environment->update(['plan_id' => $smaller->id]));
        $this->assertSame($plan->id, $environment->fresh()->plan_id);
    }

    public function test_inherited_environment_plan_change_validates_explicit_environment_user_plan(): void
    {
        $tenant = $this->tenant(['environment:users' => 10]);
        $environment = $this->environment($tenant);
        $actor = $this->environmentMember($environment);
        Model::withoutEvents(fn () => $actor->environments()->updateExistingPivot($environment->id, ['plan_id' => $this->plan(['environment:users' => 8])->id]));
        $this->rejects(fn () => PlanAssignments::updatePlanResourceLimit($tenant->plan, $this->definition('environment:users'), 5));
    }

    public function test_user_plan_cannot_drop_a_resource_with_existing_usage(): void
    {
        $tenant = $this->tenant(['tenant:users' => 10, 'tenant:environments' => 10]);
        $plan = $this->plan(['tenant:users' => 5, 'tenant:environments' => 5]);
        $actor = $this->member($tenant, $plan);
        Resource::addUsage($tenant, $this->user(), $actor);
        $this->rejects(fn () => PlanAssignments::removePlanResource($plan, $this->definition('tenant:users')));
        $newPlan = $this->plan(['tenant:environments' => 5]);
        $this->rejects(fn () => PlanAssignments::ensureAssignableToTenantUser($newPlan->id, $tenant, 'plan_id', $actor->id));
    }

    public function test_environment_user_plan_cannot_drop_a_resource_with_existing_usage(): void
    {
        $tenant = $this->tenant(['environment:users' => 10, 'tenant:users' => 10]);
        $environment = $this->environment($tenant);
        $plan = $this->plan(['environment:users' => 5, 'tenant:users' => 5]);
        $actor = $this->environmentMember($environment);
        Model::withoutEvents(fn () => $actor->environments()->updateExistingPivot($environment->id, ['plan_id' => $plan->id]));
        Resource::addEnvironmentUsage($environment, $this->user(), $actor);
        $this->rejects(fn () => PlanAssignments::removePlanResource($plan, $this->definition('environment:users')));
    }

    public function test_unlimited_reservation_cannot_survive_a_finite_parent_limit(): void
    {
        $parent = $this->tenant(['tenant:users' => -1]);
        $plan = $this->plan(['tenant:users' => -1], $parent);
        $child = Model::withoutEvents(fn () => Tenant::query()->create([
            'name' => 'Unlimited child', 'plan_id' => $plan->id, 'parent_tenant_id' => $parent->id,
        ]));
        Quota::transaction(fn () => PlanAssignments::syncTenantReservations($parent, $child, $plan));
        $this->rejects(fn () => PlanAssignments::updatePlanResourceLimit($parent->plan, $this->definition('tenant:users'), 100));
        $this->assertSame(-1, Quota::limit($parent->plan_id, 'users', 'tenant'));
    }

    public function test_failed_booking_rolls_back_the_new_object(): void
    {
        $tenant = $this->tenant(['tenant:users' => 0]);
        $actor = $this->member($tenant);
        $email = 'rollback-'.str()->ulid().'@example.test';
        try {
            Resource::transaction(function () use ($tenant, $actor, $email): void {
                $target = $this->user($email);
                Resource::addUsage($tenant, $target, $actor);
            });
            $this->fail('Expected quota failure.');
        } catch (ResourceLimitException) {
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_environment_api_returns_422_and_keeps_one_object_at_limit(): void
    {
        $tenant = $this->tenant(['tenant:environments' => 1]);
        $actor = $this->member($tenant);
        $this->makeAdmin($actor);
        $path = '/api/tenants/'.$tenant->id.'/environments';
        $this->actingAs($actor, 'sanctum')->postJson($path, ['name' => 'Allowed'])->assertCreated();
        $this->postJson($path, ['name' => 'Rejected'])->assertUnprocessable()->assertJsonValidationErrors('resources');
        $this->assertSame(1, $tenant->environments()->count());
        $this->assertSame(1, Resource::getUsage($tenant, Environment::class));
    }

    public function test_environment_user_creation_rolls_back_when_tenant_budget_rejects_membership(): void
    {
        $tenant = $this->tenant(['tenant:users' => 0, 'environment:users' => 1]);
        $environment = $this->environment($tenant);
        $actor = $this->environmentMember($environment);
        $role = $this->makeAdmin($actor);
        $email = 'rollback-api-'.str()->ulid().'@example.test';
        $this->actingAs($actor, 'sanctum')->postJson('/api/tenants/'.$tenant->id.'/environments/'.$environment->id.'/users', [
            'first_name' => 'Rejected', 'last_name' => 'User', 'email' => $email, 'password' => 'test-password',
            'tenant_role' => $role->id, 'environment_role' => $role->id,
        ])->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertSame(0, Resource::getUsage($tenant, 'users'));
    }

    public function test_environment_membership_books_creator_and_detach_releases_quota(): void
    {
        $tenant = $this->tenant(['tenant:users' => 5, 'environment:users' => 1]);
        $environment = $this->environment($tenant);
        $actor = $this->environmentMember($environment);
        $target = $this->member($tenant);
        $this->actingAs($actor);
        $target->environments()->attach($environment, ['role_id' => null]);
        $this->assertSame(1, Resource::getEnvironmentUsage($environment, 'users', $actor));
        $target->environments()->detach($environment);
        $this->assertSame(0, Resource::getEnvironmentUsage($environment, 'users'));
    }

    public function test_tenant_administrator_can_book_environment_usage_without_environment_membership(): void
    {
        $tenant = $this->tenant(['tenant:users' => 5, 'environment:users' => 1]);
        $environment = $this->environment($tenant);
        $actor = $this->member($tenant);
        $target = $this->member($tenant);
        $this->actingAs($actor);
        $target->environments()->attach($environment, ['role_id' => null]);
        $this->assertSame(1, Resource::getEnvironmentUsage($environment, 'users', $actor));
        $this->assertFalse($environment->userHasResourceAvailable($actor, 'users'));
    }

    public function test_assigned_plan_deletion_is_rejected_and_unused_plan_can_be_deleted(): void
    {
        $tenant = $this->tenant(['tenant:users' => 5]);
        $this->rejects(fn () => $tenant->plan->delete());
        $this->assertDatabaseHas('plans', ['id' => $tenant->plan_id]);
        $unused = $this->plan(['tenant:users' => 1]);
        $unused->delete();
        $this->assertDatabaseMissing('plans', ['id' => $unused->id]);
    }

    public function test_tenant_owned_plan_and_role_creation_consume_metadata_quota(): void
    {
        $tenant = $this->tenant(['tenant:plans' => 1, 'tenant:roles' => 1]);
        $actor = $this->member($tenant);
        $this->actingAs($actor);
        $plan = Plan::query()->create(['name' => 'Counted plan', 'tenant_id' => $tenant->id]);
        $role = Role::query()->create(['name' => 'Counted role', 'tenant_id' => $tenant->id]);
        $this->assertSame(1, Resource::getUsage($tenant, Plan::class));
        $this->assertSame(1, Resource::getUsage($tenant, Role::class));
        $plan->delete();
        $role->delete();
        $this->assertSame(0, Resource::getUsage($tenant, Plan::class));
    }

    public function test_shared_quota_mutex_blocks_a_second_connection(): void
    {
        $name = 'quota-lock-test';
        config(['database.connections.'.$name => config('database.connections.'.DB::getDefaultConnection())]);
        $other = DB::connection($name);
        try {
            $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $other->beginTransaction();
            $this->assertNotNull($other->table('quota_locks')->where('id', 1)->sharedLock()->first());
            $other->rollBack();
            Quota::transaction(function () use ($other): void {
                $other->beginTransaction();
                try {
                    $other->table('quota_locks')->where('id', 1)->sharedLock()->first();
                    $this->fail('Quota lock was not held.');
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

    public function test_child_tenants_consume_parent_slots_and_reserve_their_plan_atomically(): void
    {
        $parent = $this->tenant(['tenant:tenants' => 1, 'tenant:users' => 10]);
        $plan = $this->plan(['tenant:users' => 2], $parent);
        $this->actingAs($this->member($parent));
        $child = Tenant::query()->create(['name' => 'Counted child', 'parent_tenant_id' => $parent->id, 'plan_id' => $plan->id]);
        $this->assertSame(1, Resource::getUsage($parent, Tenant::class));
        $this->assertDatabaseHas('tenant_resource_reservations', ['tenant_id' => $parent->id, 'reserved_for_tenant_id' => $child->id, 'limit' => 2]);
        try {
            Tenant::query()->create(['name' => 'Rejected child', 'parent_tenant_id' => $parent->id, 'plan_id' => $plan->id]);
            $this->fail('Expected quota failure.');
        } catch (ResourceLimitException) {
            $this->assertSame(1, $parent->subTenants()->count());
        }
        $child->delete();
        $this->assertSame(0, Resource::getUsage($parent, Tenant::class));
        $this->assertSame(10, PlanAssignments::availableTenantBudget($parent)['tenant:users']);
    }

    private function rejects(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected plan validation failure.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    private function definition(string $identifier): Definition
    {
        [$type, $key] = explode(':', $identifier);
        $model = match ($key) {
            'users' => User::class, 'tenants' => Tenant::class, 'environments' => Environment::class, 'plans' => Plan::class, 'roles' => Role::class
        };

        return Definition::query()->firstOrCreate(['key' => $key, 'type' => $type], ['name' => $key, 'model_type' => $model]);
    }

    private function plan(array $limits, ?Tenant $owner = null): Plan
    {
        return Model::withoutEvents(function () use ($limits, $owner): Plan {
            $plan = Plan::query()->create(['name' => 'Quota '.str()->ulid(), 'tenant_id' => $owner?->id]);
            foreach ($limits as $identifier => $limit) {
                $plan->resources()->attach($this->definition($identifier), ['limit' => $limit]);
            }

            return $plan;
        });
    }

    private function tenant(array $limits): Tenant
    {
        return Model::withoutEvents(fn () => Tenant::query()->create(['name' => 'Quota tenant', 'plan_id' => $this->plan($limits)->id]));
    }

    private function environment(Tenant $tenant, ?Plan $plan = null): Environment
    {
        return Model::withoutEvents(fn () => Environment::query()->create(['name' => 'Quota environment', 'tenant_id' => $tenant->id, 'plan_id' => $plan?->id]));
    }

    private function user(?string $email = null): User
    {
        return User::query()->create(['first_name' => 'Quota', 'last_name' => 'Test', 'email' => $email ?? 'quota-'.str()->ulid().'@example.test', 'password' => 'test-password']);
    }

    private function member(Tenant $tenant, ?Plan $plan = null): User
    {
        $user = $this->user();
        Model::withoutEvents(fn () => $user->tenants()->attach($tenant, ['role_id' => null, 'plan_id' => $plan?->id]));

        return $user;
    }

    private function environmentMember(Environment $environment): User
    {
        $actor = $this->member($environment->tenant);
        $this->attachEnvironment($environment, $actor);

        return $actor;
    }

    private function attachEnvironment(Environment $environment, User $actor): void
    {
        Model::withoutEvents(fn () => $actor->environments()->attach($environment, ['role_id' => null]));
    }

    private function makeAdmin(User $user): Role
    {
        $role = Role::query()->create(['name' => 'Quota administrator '.str()->ulid()]);
        $permission = Permission::query()->firstOrCreate(['key' => '*'], ['name' => 'Everything']);
        $role->permissions()->attach($permission, ['inheritable' => true]);
        $user->roles()->attach($role);

        return $role;
    }
}
