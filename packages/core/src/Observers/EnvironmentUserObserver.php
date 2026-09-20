<?php

namespace Froxlor\Core\Observers;

use Froxlor\Core\Models\EnvironmentUser;
use Froxlor\Core\Support\PlanAssignments;
use Froxlor\Core\Support\Resource;

class EnvironmentUserObserver
{
    public function created(EnvironmentUser $membership): void
    {
        Resource::addEnvironmentUsage($membership->environment, $membership->user, auth()->user() ?? $membership->user);
    }

    public function updated(EnvironmentUser $membership): void
    {
        if ($membership->wasChanged('plan_id')) {
            $environment = $membership->environment()->lockForUpdate()->firstOrFail();
            PlanAssignments::ensureAssignableToEnvironmentUser($membership->plan_id,
                $environment->tenant()->lockForUpdate()->firstOrFail(), $environment, 'environment_plan', $membership->user_id);
        }
    }

    public function deleted(EnvironmentUser $membership): void
    {
        Resource::removeEnvironmentUsage($membership->environment, $membership->user);
    }
}
