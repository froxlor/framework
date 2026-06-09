<?php

namespace Froxlor\UI\Pushable;

use Froxlor\UI\Contracts\Pushable;

/**
 * @property ?array $badge
 * @property ?string $label
 * @property ?string $href
 * @property ?bool $active
 * @property ?bool $visible
 * @property ?array $icon
 */
class SidebarLink extends Pushable
{
    public array $requiredKeys = ['label'];
    public mixed $badge = null;
    public mixed $label = null;
    public mixed $href = null;
    public mixed $active = false;
    public mixed $visible = true;
    public mixed $icon = null;

    public function badge(callable|string|null $label, callable|string|null $variant = null): static
    {
        $this->badge = $label ? (object)[
            'label' => $this->call($label),
            'variant' => $this->call($variant),
        ] : null;

        return $this;
    }

    public function label(callable|string $value): static
    {
        $this->label = $value;

        return $this;
    }

    public function route(callable|string $value): static
    {
        $this->href = $value;

        return $this;
    }

    public function active(callable|bool $value): static
    {
        $this->active = $value;

        return $this;
    }

    public function visible(callable|bool $value): static
    {
        $this->visible = $value;

        return $this;
    }

    public function icon(callable|string|null $name, callable|string|null $variant = null): static
    {
        $this->icon = $name ? (object)[
            'name' => $this->call($name),
            'variant' => $this->call($variant)
        ] : null;

        return $this;
    }
}
