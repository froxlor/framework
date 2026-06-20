<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Models\EnvUsage;
use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\TenantUsage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ListPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:plans
                            {--d|details= : Show one plan by ULID or exact name }
                            {--r|resources : List all available resources instead of plans }
                            {--used-by= : Show plans using a resource key }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect plans, assigned resources, and resource usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($resourceKey = $this->option('used-by')) {
            return $this->showPlansUsingResource((string)$resourceKey);
        }

        if ($this->option('resources')) {
            $this->showResources();
            return self::SUCCESS;
        }

        if ($identifier = $this->option('details')) {
            return $this->showPlan((string)$identifier);
        }

        $this->showPlans();
        return self::SUCCESS;
    }

    /**
     * Show all plans with the number of attached resources.
     */
    private function showPlans(): void
    {
        $plans = Plan::query()
            ->withCount('resources')
            ->orderBy('name')
            ->get();

        $this->table(['ID', 'Name', 'Type', 'Tenant ID', 'Resources'], $plans->map(fn(Plan $plan) => [
            $plan->id,
            $plan->name,
            $plan->type,
            $plan->tenant_id ?? 'global',
            $plan->resources_count,
        ]));
    }

    /**
     * Show all resources with the number of plans using each resource.
     */
    private function showResources(): void
    {
        $resources = Resource::query()
            ->withCount('plans')
            ->orderBy('key')
            ->orderBy('type')
            ->get();

        $this->table(['ID', 'Key', 'Name', 'Type', 'Model', 'Plans'], $resources->map(fn(Resource $resource) => [
            $resource->id,
            $resource->key,
            $resource->name,
            $resource->type,
            $resource->model_type,
            $resource->plans_count,
        ]));
    }

    /**
     * Show all plans that contain resources with the given resource key.
     *
     * Resource keys can exist more than once for different resource scopes, for example
     * tenant-scoped and environment-scoped `users`. The output keeps those definitions
     * visible instead of collapsing them into one row.
     */
    private function showPlansUsingResource(string $resourceKey): int
    {
        $resources = Resource::query()
            ->where('key', $resourceKey)
            ->with(['plans' => fn($query) => $query->orderBy('name')])
            ->orderBy('type')
            ->orderBy('model_type')
            ->get();

        if ($resources->isEmpty()) {
            $this->error('Resource not found: ' . $resourceKey);
            return self::FAILURE;
        }

        $rows = $resources->flatMap(fn(Resource $resource) => $resource->plans->map(fn(Plan $plan) => [
            $resource->id,
            $resource->key,
            $resource->type,
            $resource->model_type,
            $plan->id,
            $plan->name,
            $plan->tenant_id ?? 'global',
            $this->formatLimit((int)$plan->pivot->limit),
        ]));

        $this->table(['Resource ID', 'Key', 'Type', 'Model', 'Plan ID', 'Plan', 'Tenant ID', 'Limit'], $rows);

        if ($rows->isEmpty()) {
            $this->warn('No plans use resource key: ' . $resourceKey);
        }

        return self::SUCCESS;
    }

    /**
     * Show one plan and list its attached resources with limits and global usage.
     */
    private function showPlan(string $identifier): int
    {
        $plan = $this->findPlan($identifier);

        if (!$plan) {
            $this->error('Plan not found: ' . $identifier);
            return self::FAILURE;
        }

        $this->info($plan->name . ' (' . $plan->id . ')');
        $this->line('Type: ' . $plan->type);
        $this->line('Tenant: ' . ($plan->tenant_id ?? 'global'));
        $this->newLine();

        $resources = $plan->resources()
            ->orderBy('resources.key')
            ->orderBy('resources.type')
            ->get();

        $this->table(['Resource', 'Type', 'Model', 'Limit', 'Current usage'], $resources->map(fn(Resource $resource) => [
            $resource->key,
            $resource->type,
            $resource->model_type,
            $this->formatLimit((int)$resource->pivot->limit),
            $this->usageFor($resource),
        ]));

        return self::SUCCESS;
    }

    /**
     * Resolve a plan by ULID or exact plan name.
     */
    private function findPlan(string $identifier): ?Plan
    {
        return Plan::query()
            ->where('id', $identifier)
            ->orWhere(fn(Builder $query) => $query->where('name', $identifier))
            ->first();
    }

    /**
     * Convert stored plan limits to readable CLI output.
     */
    private function formatLimit(int $limit): string
    {
        return $limit === -1 ? 'unlimited' : (string)$limit;
    }

    /**
     * Count current global usage for the resource type represented by a plan resource.
     */
    private function usageFor(Resource $resource): int
    {
        $query = $resource->type === 'environment'
            ? EnvUsage::query()
            : TenantUsage::query();

        return $query->where('resource_key', $resource->key)->count();
    }
}
