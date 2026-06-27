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
use Froxlor\Core\Support\Audit;
use Froxlor\Core\Support\Resource;
use Throwable;

class EnvironmentObserver
{
    /**
     * Ensure the target tenant may consume another environment resource.
     *
     * When a parent tenant user creates an environment for a subtenant, both
     * the acting tenant and the target tenant must have capacity available.
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
        $actingTenant = Resource::actingTenantFor(auth()->user(), $targetTenant);

        if ($actingTenant === null
            || !Resource::hasUsageAvailable($actingTenant, Environment::class, auth()->user())
            || (!$actingTenant->is($targetTenant) && !Resource::hasUsageAvailable($targetTenant, Environment::class, auth()->user()))) {
            throw new ResourceLimitException('Resource limit exceeded (' . Environment::getResourceKey() . ')');
        }
    }

    /**
     * Record tenant-level usage for a newly created environment.
     *
     * Usage is booked on the tenant the user acts from and, when creating for a
     * subtenant, also on the target tenant so both scopes reflect consumption.
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

        $actingTenant = Resource::actingTenantFor(auth()->user(), $environment->tenant);
        if ($actingTenant === null) {
            return;
        }

        Resource::addUsage($actingTenant, $environment, auth()->user());
        if (!$actingTenant->is($environment->tenant)) {
            Resource::addUsage($environment->tenant, $environment, auth()->user());
        }

        Audit::notice('environment "' . $environment->name . '" created', $environment->tenant, $environment, [
            'plan_id' => $environment->plan_id,
        ]);
    }

    /**
     * Record an audit log entry for environment changes.
     */
    public function updated(Environment $environment): void
    {
        Audit::info('environment "' . $environment->name . '" updated', $environment->tenant, $environment, [
            'plan_id' => $environment->plan_id,
        ]);
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

        Audit::info('environment "' . $environment->name . '" deleted', $environment->tenant, $environment, [
            'plan_id' => $environment->plan_id,
        ]);
    }
}
