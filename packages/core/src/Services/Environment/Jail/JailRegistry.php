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

    /** Merge declarative package state; all conflicts fail before infrastructure changes. */
    public function plan(JailContext $context, ?\Froxlor\Core\Models\Environment $environment = null): array
    {
        $result = ['binaries' => [], 'users' => [], 'files' => [], 'directories' => [], 'environment' => [], 'providers' => []];
        $providers = $this->providers;
        ksort($providers);
        foreach ($providers as $key => $provider) {
            $settings = EnvironmentSettings::resolve($environment ?? throw new InvalidArgumentException('Environment is required for jail planning.'), $provider);
            $plan = $provider->plan($context, $settings)->toArray();
            $result['providers'][$key] = hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
            $result['binaries'] = array_values(array_unique([...$result['binaries'], ...$plan['binaries']]));
            foreach (['files', 'directories', 'environment'] as $kind) {
                foreach ($plan[$kind] as $name => $definition) {
                    if (isset($result[$kind][$name]) && $result[$kind][$name] !== $definition) {
                        throw new LogicException('Conflicting jail '.$kind.' definition.');
                    }
                    $result[$kind][$name] = $definition;
                }
            }
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
        ksort($result['files']);
        ksort($result['directories']);
        ksort($result['environment']);

        return $result;
    }
}
