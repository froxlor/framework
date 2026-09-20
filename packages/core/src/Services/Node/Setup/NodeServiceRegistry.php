<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Services\Node\Platform\NodePlatform;
use Froxlor\Core\Support\SettingRegistry;
use InvalidArgumentException;
use LogicException;

/** Application-scoped registry: registration never writes to a node or database. */
final class NodeServiceRegistry
{
    /** @var array<string, NodeServiceProvider> */
    private array $providers = [];

    /** @param class-string<NodeServiceProvider>|NodeServiceProvider $provider */
    public function register(string|NodeServiceProvider $provider): void
    {
        if (is_string($provider)) {
            $provider = app($provider);
        }
        if (! $provider instanceof NodeServiceProvider
            || ! preg_match('/^[a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*$/D', $provider->package())
            || ! str_starts_with($provider->key(), $provider->package().':')
            || ! preg_match('/^[a-z0-9-]+\/[a-z0-9-]+:[a-z0-9][a-z0-9-]*$/D', $provider->key())
            || ! preg_match('/^[a-z0-9][a-z0-9-]*$/D', $provider->role())
            || $provider->revision() === '' || $provider->platforms() === []) {
            throw new InvalidArgumentException('Invalid node service provider definition.');
        }

        $existing = $this->providers[$provider->key()] ?? null;
        if ($existing !== null) {
            if ($existing::class !== $provider::class || $this->describe($existing) !== $this->describe($provider)) {
                throw new LogicException('Node service provider key already registered.');
            }

            return;
        }

        $settings = [];
        foreach ($provider->settings() as $name => $definition) {
            if (! is_string($name) || ! preg_match('/^[a-z][a-z0-9_]*$/D', $name) || ! $definition instanceof SettingDefinition) {
                throw new InvalidArgumentException('Invalid node service setting definition.');
            }
            $settings[] = ['path' => ServiceSettings::path($provider, $name)];
        }
        SettingRegistry::register($settings, $provider->package());
        $this->providers[$provider->key()] = $provider;
    }

    public function get(string $key): NodeServiceProvider
    {
        return $this->providers[$key] ?? throw new InvalidArgumentException('Node service provider is not registered.');
    }

    /** Metadata for future API/UI consumers; does not expose setting values. */
    public function available(?NodePlatform $platform = null): array
    {
        $providers = $this->providers;
        ksort($providers);

        return array_values(array_map($this->describe(...), array_filter($providers,
            fn (NodeServiceProvider $provider) => $platform === null
                || ($platform->supported && in_array($platform->key(), $provider->platforms(), true)),
        )));
    }

    private function describe(NodeServiceProvider $provider): array
    {
        return [
            'key' => $provider->key(),
            'role' => $provider->role(),
            'package' => $provider->package(),
            'revision' => $provider->revision(),
            'platforms' => $provider->platforms(),
            'requires' => $provider->requires(),
            'conflicts' => $provider->conflicts(),
            'settings' => array_map(fn (SettingDefinition $setting) => $setting->toArray(), $provider->settings()),
        ];
    }
}
