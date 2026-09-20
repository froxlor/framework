<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Setting;
use InvalidArgumentException;

/** Resolves existing global/type/node settings, preserving package ownership on writes. */
final readonly class ServiceSettings
{
    private function __construct(private array $values) {}

    public static function path(NodeServiceProvider $provider, string $name): string
    {
        return 'services.'.$provider->key().'.'.$name;
    }

    public static function resolve(Node $node, NodeServiceProvider $provider, array $overrides = []): self
    {
        $definitions = $provider->settings();
        if (array_diff_key($overrides, $definitions) !== []) {
            throw new InvalidArgumentException('Unknown node service settings.');
        }

        $values = [];
        foreach ($definitions as $name => $definition) {
            $value = array_key_exists($name, $overrides)
                ? $overrides[$name]
                : $node->getSetting(self::path($provider, $name), $definition->default);
            $values[$name] = $definition->normalize($value);
        }

        return new self($values);
    }

    /** Validate the whole update before storing any value. Call after authorization. */
    public static function store(Node $node, NodeServiceProvider $provider, array $values): void
    {
        if (! $node->exists) {
            throw new InvalidArgumentException('Settings require a persisted node.');
        }
        $resolved = self::resolve($node, $provider, $values);
        $node->getConnection()->transaction(function () use ($node, $provider, $values, $resolved): void {
            foreach ($values as $name => $_) {
                $value = $resolved->values[$name];
                Setting::setValueForModel(
                    $node,
                    self::path($provider, $name),
                    is_bool($value) ? (int) $value : $value,
                    type: $provider->settings()[$name]->type === 'choice' ? 'string' : $provider->settings()[$name]->type,
                    source: $provider->package(),
                );
            }
        });
    }

    public function integer(string $name): int
    {
        return $this->typed($name, 'integer');
    }

    public function boolean(string $name): bool
    {
        return $this->typed($name, 'boolean');
    }

    public function string(string $name): string
    {
        return $this->typed($name, 'string');
    }

    private function typed(string $name, string $type): int|bool|string
    {
        if (! array_key_exists($name, $this->values) || gettype($this->values[$name]) !== $type) {
            throw new InvalidArgumentException('Unknown setting or incorrect setting accessor.');
        }

        return $this->values[$name];
    }
}
