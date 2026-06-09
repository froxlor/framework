<?php

namespace Froxlor\UI\Contracts;

use Froxlor\UI\Concerns\HasCallable;
use Froxlor\UI\Support\AttributeResolver;

abstract class Component implements Payloadable, Resolvable
{
    use HasCallable;

    public string $key;

    public ?string $label = null;

    public mixed $default = null;

    public ?array $rules = null;

    public bool $required = false;

    public mixed $visible = true;

    public array $schema = [];

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

    public function required(callable|bool $value = true): static
    {
        $this->required = $value;
        return $this;
    }

    public function visible(callable|bool $value = true): static
    {
        $this->visible = $value;

        return $this;
    }

    public function resolve(array $context = []): static
    {
        $clone = clone $this;
        $clone->label = AttributeResolver::value($this->label, $context);
        $clone->default = AttributeResolver::value($this->default, $context);
        $clone->rules = AttributeResolver::value($this->rules, $context);
        $clone->required = AttributeResolver::value($this->required, $context) ?? false;
        $clone->visible = AttributeResolver::value($this->visible, $context) ?? true;
        $clone->schema = $this->serializeSchema($this->schema, $context);

        return $clone;
    }

    public function toPayload(): array
    {
        return [
            'key' => $this->key,
            'label' => AttributeResolver::value($this->label),
            'default' => AttributeResolver::value($this->default),
            'rules' => AttributeResolver::value($this->rules),
            'required' => AttributeResolver::value($this->required) ?? false,
            'visible' => AttributeResolver::value($this->visible) ?? true,
            'view' => $this->view,
            'schema' => $this->schema,
        ];
    }

    public function toObject(): object
    {
        return (object)$this->toPayload();
    }

    protected function serializeSchema(array $items, array $context = []): array
    {
        return array_map(function ($item) use ($context) {
            if ($item instanceof ResourceComponent) {
                return $item;
            }

            if ($item instanceof Resolvable) {
                $item = $item->resolve($context);
            }

            if ($item instanceof Payloadable) {
                return (object)$item->toPayload();
            }

            if (method_exists($item, 'toObject')) {
                return $item->toObject();
            }

            return is_array($item) ? (object)$item : $item;
        }, $items);
    }
}
