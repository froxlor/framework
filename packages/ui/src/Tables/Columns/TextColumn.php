<?php

namespace Froxlor\UI\Tables\Columns;

use Froxlor\UI\Contracts\Column;
use Froxlor\UI\Support\AttributeResolver;
use Illuminate\Support\Carbon;

class TextColumn extends Column
{

    public bool $html = false;

    public bool $small = false;

    public mixed $formatValue = null;

    public array $descriptionLines = [];

    public string $view = 'ui::schema.tables.columns.text-column';

    public function date(string $format = 'Y-m-d'): static
    {
        $this->formatValue(fn ($value) => Carbon::parse($value)->format($format));

        return $this;
    }

    public function dateTime(string $format = 'Y-m-d H:i:s'): static
    {
        $this->formatValue(fn ($value) => Carbon::parse($value)->format($format));

        return $this;
    }

    public function formatValue(callable|string|null $value): static
    {
        $this->formatValue = $value;

        return $this;
    }

    public function html(callable|bool $value = true): static
    {
        $this->html = $value;

        return $this;
    }

    /**
     * Render the column's value in small, muted text instead of the default size.
     */
    public function small(callable|bool $value = true): static
    {
        $this->small = $value;

        return $this;
    }

    /**
     * Stack a line of small, muted text below the column's primary value (e.g. a
     * description, author, or metadata line). Callable multiple times to stack
     * several lines. The value may be a plain string or a closure resolved per
     * row (receiving `row`, `value` and `column`, like formatValue()), so it can
     * pull in other fields from the row. Pass $html = true to render markup
     * (e.g. a link) instead of escaped text.
     */
    public function description(callable|string|null $value, bool $html = false): static
    {
        $this->descriptionLines[] = [
            'value' => $value,
            'html' => $html,
        ];

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = parent::resolve($context);
        $clone->html = (bool)AttributeResolver::value($this->html, $context);
        $clone->small = (bool)AttributeResolver::value($this->small, $context);
        $clone->formatValue = $this->formatValue;
        $clone->descriptionLines = $this->descriptionLines;

        return $clone;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();
        $payload['html'] = AttributeResolver::value($this->html) ?? false;
        $payload['small'] = AttributeResolver::value($this->small) ?? false;
        $payload['formatValue'] = $this->formatValue;
        $payload['descriptionLines'] = $this->descriptionLines;

        return $payload;
    }
}
