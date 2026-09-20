<?php

namespace Tests\Fakes;

use Froxlor\Core\Models\Permission;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Role;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Quota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/** Used only inside tests with DatabaseTransactions; never mutates seeded fixtures. */
trait BuildsResourceUsageFixtures
{
    private Tenant $quotaTenant;

    private Plan $quotaPlan;

    private User $quotaActor;

    private function buildResourceUsageFixtures(): void
    {
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('q', 32))]);
        Bus::fake();
        Model::withoutEvents(function (): void {
            $this->quotaPlan = Plan::query()->create(['name' => 'Resource test '.str()->ulid()]);
            foreach (Resource::query()->get() as $resource) {
                $this->quotaPlan->resources()->attach($resource, ['limit' => -1]);
            }
            $this->quotaTenant = Tenant::query()->create(['name' => 'Resource test', 'plan_id' => $this->quotaPlan->id]);
            $this->quotaActor = User::query()->create([
                'first_name' => 'Resource', 'last_name' => 'Test',
                'email' => 'resource-'.str()->ulid().'@example.test', 'password' => 'test-password',
            ]);
            $role = Role::query()->create(['name' => 'Resource test administrator '.str()->ulid()]);
            $permission = Permission::query()->firstOrCreate(['key' => '*'], ['name' => 'Everything']);
            $role->permissions()->attach($permission, ['inheritable' => true]);
            $this->quotaActor->roles()->attach($role);
            $this->quotaActor->tenants()->attach($this->quotaTenant, ['role_id' => $role->id]);
        });
    }

    private function quotaChild(): Tenant
    {
        return Model::withoutEvents(function (): Tenant {
            $plan = Plan::query()->create(['name' => 'Resource child plan', 'tenant_id' => $this->quotaTenant->id]);
            foreach ($this->quotaPlan->resources as $resource) {
                $plan->resources()->attach($resource, ['limit' => -1]);
            }
            $child = Tenant::query()->create(['name' => 'Resource child', 'parent_tenant_id' => $this->quotaTenant->id, 'plan_id' => $plan->id]);
            Quota::transaction(fn () => PlanAssignments::syncTenantReservations($this->quotaTenant, $child, $plan));

            return $child;
        });
    }
}
