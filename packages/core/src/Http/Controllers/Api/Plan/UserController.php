<?php

namespace Froxlor\Core\Http\Controllers\Api\Plan;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * List every assignment currently using the given global plan.
     *
     * Plans can be assigned to tenants, environments, tenant-user pivots, and
     * environment-user pivots. Returning all assignment types gives the UI the same
     * "who uses this" visibility that roles expose through their user relation.
     */
    public function index(Plan $plan): JsonResponse
    {
        Gate::authorize('usersViewAny', $plan);

        return response()->json([
            'data' => [
                ...$this->tenantAssignments($plan),
                ...$this->environmentAssignments($plan),
                ...$this->tenantUserAssignments($plan),
                ...$this->environmentUserAssignments($plan),
            ],
        ]);
    }

    private function tenantAssignments(Plan $plan): array
    {
        return Tenant::query()
            ->where('plan_id', $plan->id)
            ->orderBy('name')
            ->get()
            ->map(fn(Tenant $tenant) => [
                'type' => 'tenant',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'environment_id' => null,
                'environment_name' => null,
                'user_id' => null,
                'user_name' => null,
                'user_email' => null,
            ])
            ->all();
    }

    private function environmentAssignments(Plan $plan): array
    {
        return Environment::query()
            ->with('tenant')
            ->where('plan_id', $plan->id)
            ->orderBy('name')
            ->get()
            ->map(fn(Environment $environment) => [
                'type' => 'environment',
                'tenant_id' => $environment->tenant_id,
                'tenant_name' => $environment->tenant?->name,
                'environment_id' => $environment->id,
                'environment_name' => $environment->name,
                'user_id' => null,
                'user_name' => null,
                'user_email' => null,
            ])
            ->all();
    }

    private function tenantUserAssignments(Plan $plan): array
    {
        return DB::table('tenant_user')
            ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->join('users', 'users.id', '=', 'tenant_user.user_id')
            ->select([
                'tenant_user.tenant_id',
                'tenant_user.user_id',
                'tenants.name as tenant_name',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.email as user_email',
            ])
            ->where('tenant_user.plan_id', $plan->id)
            ->orderBy('tenants.name')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get()
            ->map(fn(object $assignment) => [
                'type' => 'tenant_user',
                'tenant_id' => $assignment->tenant_id,
                'tenant_name' => $assignment->tenant_name,
                'environment_id' => null,
                'environment_name' => null,
                'user_id' => $assignment->user_id,
                'user_name' => trim($assignment->user_first_name . ' ' . $assignment->user_last_name),
                'user_email' => $assignment->user_email,
            ])
            ->all();
    }

    private function environmentUserAssignments(Plan $plan): array
    {
        return DB::table('environment_user')
            ->join('environments', 'environments.id', '=', 'environment_user.environment_id')
            ->join('tenants', 'tenants.id', '=', 'environments.tenant_id')
            ->join('users', 'users.id', '=', 'environment_user.user_id')
            ->select([
                'environments.tenant_id',
                'environment_user.environment_id',
                'environment_user.user_id',
                'tenants.name as tenant_name',
                'environments.name as environment_name',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.email as user_email',
            ])
            ->where('environment_user.plan_id', $plan->id)
            ->orderBy('tenants.name')
            ->orderBy('environments.name')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get()
            ->map(fn(object $assignment) => [
                'type' => 'environment_user',
                'tenant_id' => $assignment->tenant_id,
                'tenant_name' => $assignment->tenant_name,
                'environment_id' => $assignment->environment_id,
                'environment_name' => $assignment->environment_name,
                'user_id' => $assignment->user_id,
                'user_name' => trim($assignment->user_first_name . ' ' . $assignment->user_last_name),
                'user_email' => $assignment->user_email,
            ])
            ->all();
    }
}
