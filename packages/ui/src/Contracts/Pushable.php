<?php

namespace Froxlor\UI\Contracts;

use Exception;
use Froxlor\UI\Concerns\HasCallable;
use Froxlor\UI\Contracts\Payloadable;
use Froxlor\UI\Contracts\Resolvable;
use Froxlor\UI\Support\AttributeResolver;

abstract class Pushable implements Payloadable, Resolvable
{
    use HasCallable;

    public array $children = [];

    public array $requiredKeys = [];

    public function __construct(public string $key, public int $group = 0)
    {
    }

    public static function make(string $key, int $group = 0): static
    {
        return new static($key, $group);
    }

    public function resolve(array $context = []): static
    {
        $clone = clone $this;

        foreach (get_object_vars($this) as $property => $value) {
            if (in_array($property, ['children', 'requiredKeys', 'key', 'group'], true)) {
                continue;
            }
            $clone->{$property} = AttributeResolver::value($value, $context);
        }

        $clone->children = array_map(function ($child) use ($context) {
            if ($child instanceof Resolvable) {
                return $child->resolve($context);
            }
            return $child;
        }, $this->children);

        return $clone;
    }

    public function toPayload(): array
    {
        $resolved = $this->resolve();

        return get_object_vars($resolved);
    }

    public function toObject(): object
    {
        return (object)$this->toPayload();
    }
}
