<?php

namespace Froxlor\UI\Contracts;

use Froxlor\UI\Concerns\HasCallable;
use Froxlor\UI\Contracts\Payloadable;
use Froxlor\UI\Contracts\Resolvable;
use Froxlor\UI\Support\AttributeResolver;

abstract class Column implements Payloadable, Resolvable
{
    use HasCallable;

    public string $key;

    public ?string $label = null;

    public ?string $type = null;

    public bool $sortable = false;

    public bool $searchable = false;

    public bool $toggleable = false;

    public bool $isHiddenByDefault = false;

    protected string $view;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(callable|string|null $value): static
    {
        $this->label = $value;

        return $this;
    }

    public function boolean(): static
    {
        $this->type = 'boolean';

        return $this;
    }

    public function sortable(callable|bool $value = true): static
    {
        $this->sortable = $value;

        return $this;
    }

    public function searchable(callable|bool $value = true): static
    {
        $this->searchable = $value;

        return $this;
    }

    public function toggleable(callable|bool $value = true, bool $isHiddenByDefault = false): static
    {
        $this->toggleable = $value;
        $this->isHiddenByDefault = $isHiddenByDefault;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = clone $this;
        $clone->label = AttributeResolver::value($this->label, $context);
        $clone->type = AttributeResolver::value($this->type, $context);
        $clone->sortable = (bool)AttributeResolver::value($this->sortable, $context);
        $clone->searchable = (bool)AttributeResolver::value($this->searchable, $context);
        $clone->toggleable = (bool)AttributeResolver::value($this->toggleable, $context);
        $clone->isHiddenByDefault = (bool)AttributeResolver::value($this->isHiddenByDefault, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        return [
            'key' => $this->key,
            'label' => AttributeResolver::value($this->label),
            'type' => AttributeResolver::value($this->type),
            'sortable' => AttributeResolver::value($this->sortable) ?? false,
            'searchable' => AttributeResolver::value($this->searchable) ?? false,
            'toggleable' => AttributeResolver::value($this->toggleable) ?? false,
            'isHiddenByDefault' => AttributeResolver::value($this->isHiddenByDefault) ?? false,
            'view' => $this->view,
        ];
    }

    public function toObject(): object
    {
        return (object)$this->toPayload();
    }
}
