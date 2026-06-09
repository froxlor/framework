<?php

namespace Froxlor\UI\Concerns;

/**
 * @property ?array $props
 */
trait HasProps
{
    public array $props = [];

    public function props(array $props): static
    {
        $this->props = $props;

        return $this;
    }
}
