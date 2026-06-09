<?php

namespace Froxlor\UI\Concerns;

use Closure;
use Froxlor\UI\Contracts\Payloadable;
use Froxlor\UI\Contracts\Resolvable;
use Froxlor\UI\Contracts\ResourceComponent;
use Froxlor\UI\Schemas\Schema;

/**
 * @property ?array $schema
 */
trait HasComponent
{
    public function components(array $schema): static
    {
        // load schema from stack
        $stack = array_map(function ($item) {
            return $item instanceof Closure ? app()->call($item, $this->props ?? []) : $item;
        }, Schema::stacks($this->key));

        $items = array_merge($schema, $stack);

        if (!$this instanceof ResourceComponent) {
            $this->schema = $items;

            return $this;
        }

        // merge schema with schema from stack
        $this->schema = array_map(function ($item) {
            // keep ResourceComponent instances intact so Livewire type-hints match.
            if ($item instanceof ResourceComponent) {
                return $item;
            }

            if ($item instanceof Resolvable) {
                $item = $item->resolve($this->props ?? []);
            }

            if ($item instanceof Payloadable) {
                return $item->toPayload();
            }

            if (method_exists($item, 'toObject')) {
                return (array)$item->toObject();
            }

            return (array)$item;
        }, $items);

        return $this;
    }
}
