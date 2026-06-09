<?php

namespace Froxlor\UI\Concerns;

/**
 * @property ?array $sort
 */
trait HasSort
{
    public ?int $sort = null;

    public function sort(int $value): static
    {
        $this->sort = $value;

        return $this;
    }
}
