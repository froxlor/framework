<?php

namespace Froxlor\Core\Services\Environment\Jail;

use InvalidArgumentException;
use LogicException;

final class JailRegistry
{
    private array $providers = [];

    public function register(string|JailProvider $provider): void
    {
        $provider = is_string($provider) ? app($provider) : $provider;
        if (! $provider instanceof JailProvider || ! preg_match('/^[a-z0-9-]+\/[a-z0-9-]+:[a-z0-9-]+$/D', $provider->key())) {
            throw new InvalidArgumentException('Invalid jail provider.');
        }
        if (isset($this->providers[$provider->key()]) && $this->providers[$provider->key()] !== $provider) {
            throw new LogicException('Jail provider key already registered.');
        }
        $this->providers[$provider->key()] = $provider;
    }

    /** Union shared binaries/users; conflicting identities fail before infrastructure changes. */
    public function plan(JailContext $context): array
    {
        $result = ['binaries' => [], 'users' => [], 'providers' => []];
        $providers = $this->providers;
        ksort($providers);
        foreach ($providers as $key => $provider) {
            $plan = $provider->plan($context)->toArray();
            $result['providers'][$key] = hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
            $result['binaries'] = array_values(array_unique([...$result['binaries'], ...$plan['binaries']]));
            foreach ($plan['users'] as $name => $user) {
                if ($name === $context->user || $user['uid'] === $context->guid || $user['gid'] === $context->guid
                    || (isset($result['users'][$name]) && $result['users'][$name] !== $user)) {
                    throw new LogicException('Provider conflicts with a managed or primary jail identity.');
                }
                foreach ($result['users'] as $otherName => $other) {
                    if ($otherName !== $name && ($other['uid'] === $user['uid'] || $other['gid'] === $user['gid'])) {
                        throw new LogicException('Jail user UID/GID collision.');
                    }
                }
                $result['users'][$name] = $user;
            }
        }
        sort($result['binaries']);
        ksort($result['users']);

        return $result;
    }
}
