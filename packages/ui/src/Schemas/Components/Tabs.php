<?php

namespace Froxlor\UI\Schemas\Components;

use Froxlor\UI\Concerns\HasComponent;
use Froxlor\UI\Concerns\HasProps;
use Froxlor\UI\Contracts\Input;

class Tabs extends Input
{
    use HasComponent, HasProps;

    public bool $overhang = true;

    public string $view = 'ui::schema.components.tabs';

    public function overhang(bool $value = true): static
    {
        $this->overhang = $value;

        return $this;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'overhang' => $this->overhang,
        ]);
    }

    public function resolve(array $context = []): static
    {
        /** @var static $clone */
        $clone = parent::resolve($context);
        $clone->overhang = $this->overhang;

        return $clone;
    }
}
