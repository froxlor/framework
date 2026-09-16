<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Models\Node;
use InvalidArgumentException;
use LogicException;

/** Resolves explicitly selected providers in dependency order, without infrastructure writes. */
final readonly class NodeServicePlanner
{
    public function __construct(private NodeServiceRegistry $registry) {}

    /** @param array<string, string> $selection Service role => provider key. */
    public function plan(Node $node, array $selection, array $settings = []): NodeSetupPlan
    {
        if ((string) $node->getKey() === '' || ! $node->platform()->supported || $selection === []) {
            throw new InvalidArgumentException('A node, supported platform and service selection are required.');
        }
        if (array_diff_key($settings, array_flip(array_values($selection))) !== []) {
            throw new InvalidArgumentException('Settings supplied for an unselected provider.');
        }

        ksort($selection);
        $providers = [];
        foreach ($selection as $role => $key) {
            $provider = $this->registry->get($key);
            if ($provider->role() !== $role) {
                throw new InvalidArgumentException('Selected provider does not implement the requested role.');
            }
            foreach ($provider->conflicts() as $conflict) {
                if (isset($selection[$conflict]) || in_array($conflict, $selection, true)) {
                    throw new LogicException('Conflicting node services selected.');
                }
            }
            $providers[$role] = $provider;
        }

        $visiting = [];
        $ordered = [];
        $visit = function (string $role) use (&$visit, &$visiting, &$ordered, $providers): void {
            if (isset($ordered[$role])) {
                return;
            }
            if (isset($visiting[$role])) {
                throw new LogicException('Cyclic node service dependency.');
            }
            $provider = $providers[$role] ?? throw new LogicException('Required node service role is not selected.');
            $visiting[$role] = true;
            foreach ($provider->requires() as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$role]);
            $ordered[$role] = $provider;
        };
        foreach (array_keys($providers) as $role) {
            $visit($role);
        }

        $services = [];
        $targets = [];
        foreach ($ordered as $provider) {
            $context = NodeServiceContext::forNode($node, $provider, $settings[$provider->key()] ?? []);
            $plan = $provider->plan($context);
            $plan->assertValid();
            foreach ($plan->operations() as $operation) {
                $target = match ($operation->type) {
                    'config' => 'file:'.$operation->payload()['path'],
                    'service' => 'service:'.$operation->payload()['name'],
                    default => null,
                };
                if ($target !== null) {
                    if (isset($targets[$target])) {
                        throw new LogicException('Multiple operations own the same configuration or service.');
                    }
                    $targets[$target] = true;
                }
            }
            $services[$provider->key()] = ['role' => $provider->role(), 'revision' => $provider->revision(), 'plan' => $plan];
        }

        // Metrics can change between exploration runs; connection identity must not.
        $connection = array_intersect_key($node->properties ?? [], array_flip(['ssh_key', 'port', 'host_key']));
        ksort($connection);
        $attributes = $node->getAttributes();
        $targetFingerprint = hash('sha256', json_encode([
            $attributes['adapter'] ?? null, $attributes['hostname'] ?? null,
            $attributes['username'] ?? null, (bool) ($attributes['sudo'] ?? false),
            $attributes['password'] ?? null, $connection,
        ], JSON_THROW_ON_ERROR));

        return new NodeSetupPlan((string) $node->getKey(), $node->platform()->key(), $services, $targetFingerprint);
    }
}
