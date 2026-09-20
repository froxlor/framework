<?php

namespace Froxlor\Core\Services\Node\Setup;

use Closure;
use Froxlor\Core\Models\User;

/** Establish the initiating user for Gate and Audit, without leaking it to the next job. */
final class NodeSetupActor
{
    public function run(User $actor, Closure $callback): mixed
    {
        $guard = auth()->guard();
        $previousUser = $guard->user();
        $request = request();
        $previousResolver = $request->getUserResolver();

        try {
            $guard->setUser($actor);
            $request->setUserResolver(fn () => $actor);

            return $callback();
        } finally {
            if ($previousUser !== null) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
            $request->setUserResolver($previousResolver);
        }
    }
}
