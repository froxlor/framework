<?php

namespace Froxlor\UI\Schemas\Components;

use Froxlor\UI\Concerns\HasComponent;
use Froxlor\UI\Contracts\Component;

/**
 * @property ?array $schema
 */
class Group extends Component
{
    use HasComponent;

    public string $view = 'ui::schema.components.group';

    public int|string $col_span = 1;

    public function colSpan(int $cols): static
    {
        $this->col_span = $cols;

        return $this;
    }

    public function colSpanFull(): static
    {
        $this->col_span = 'full';

        return $this;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['col_span'] = $this->col_span;

        return $payload;
    }
}
