<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\ResourceNotFoundException;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Jobs\Environment\DeleteEnvironment;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\TenantUsage;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\Resource;
use Throwable;

class EnvironmentObserver
{
    /**
     * Ensure the target tenant may consume another environment resource.
     *
     * Check the owning tenant. Ancestors already reserved this child's budget.
     *
     * @throws InvalidResourceException
     * @throws ResourceLimitException
     * @throws UnknownTenantUserException
     */
    public function creating(Environment $environment): void
    {
        if (empty($environment->tenant_id) || !auth()->check()) {
            return;
        }

        $targetTenant = Tenant::query()->findOrFail($environment->tenant_id);
        if (!Resource::hasUsageAvailable($targetTenant, Environment::class, auth()->user())) {
            throw new ResourceLimitException('Resource limit exceeded (' . Environment::getResourceKey() . ')');
        }
    }

    /**
     * Record tenant-level usage for a newly created environment.
     *
     * Usage is booked only on the owning tenant; child reservations account for
     * delegated capacity at each ancestor without charging that capacity twice.
     *
     * @param Environment $environment
     * @throws InvalidResourceException
     * @throws ResourceLimitException
     * @throws UnknownTenantUserException
     * @throws ResourceNotFoundException
     * @throws UnknownEnvironmentUserException
     */
    public function created(Environment $environment): void
    {
        if (empty($environment->tenant_id) || !auth()->check()) {
            return;
        }

        // Ancestors already reserve the child's plan; charge only the owner.
        Resource::addUsage($environment->tenant, $environment, auth()->user());

    }

    /**
     * Record an audit log entry for environment changes.
     */
    public function updated(Environment $environment): void
    {
        if ($environment->wasChanged('plan_id')) {
            \Froxlor\Core\Support\PlanAssignments::ensureAssignableToEnvironment(
                $environment->plan_id, $environment->tenant()->lockForUpdate()->firstOrFail(), 'plan_id', $environment);
        }
    }

    /**
     * Remove assigned node data before the database resource is deleted.
     *
     * The cleanup runs synchronously so failed node cleanup prevents orphaned
     * jails while keeping the environment and pivot rows available for retry.
     *
     * @throws Throwable
     */
    public function deleting(Environment $environment): void
    {
        DeleteEnvironment::dispatchSync($environment);
    }

    /**
     * Record an audit log entry after an environment has been deleted.
     */
    public function deleted(Environment $environment): void
    {
        TenantUsage::query()
            ->where('resource_key', Environment::getResourceKey())
            ->where('resource_id', $environment->id)
            ->delete();

    }
}
