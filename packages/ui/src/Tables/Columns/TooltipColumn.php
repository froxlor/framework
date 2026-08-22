<?php

namespace Froxlor\UI\Tables\Columns;

/**
 * A TextColumn that wraps its value in a hover tooltip. Use it for values that can
 * grow long (lists, JSON blobs, etc.) so the visible cell stays compact — render a
 * short summary via formatValue() and the full detail via tooltip() — without
 * breaking the table's row height.
 */
class TooltipColumn extends TextColumn
{
    public mixed $tooltip = null;

    public string $view = 'ui::schema.tables.columns.tooltip-column';

    /**
     * Set the tooltip content shown on hover. Accepts a plain string or a closure
     * resolved per row (receiving `value`, `row` and `column`, like formatValue()).
     * Always rendered as raw HTML (e.g. so a `<br>`-separated list can be passed),
     * so escape any untrusted content yourself before returning it.
     */
    public function tooltip(callable|string|null $value): static
    {
        $this->tooltip = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->tooltip = $this->tooltip;

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['tooltip'] = $this->tooltip;

        return $payload;
    }
}
