<?php

namespace Froxlor\UI\Contracts;

use Froxlor\UI\Concerns\HasCallable;
use Froxlor\UI\Support\AttributeResolver;

abstract class Input implements Payloadable, Resolvable
{
    use HasCallable;

    public string $key;

    public ?string $col = null;

    public mixed $default = null;

    public ?string $label = null;

    public ?array $rules = null;

    public bool $required = false;

    public ?string $type = null;

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

    public function col(callable|string|null $value): static
    {
        $this->col = $value;

        return $this;
    }

    public function default(callable|string|null $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function label(callable|string|null $value): static
    {
        $this->label = trans($value);

        return $this;
    }

    public function rules(callable|array $value): static
    {
        $this->rules = $value;

        return $this;
    }

    public function required(callable|bool $value = true): static
    {
        $this->required = $value;

        return $this;
    }

    public function toPayload(): array
    {
        return [
            'key' => $this->key,
            'col' => AttributeResolver::value($this->col),
            'default' => AttributeResolver::value($this->default),
            'label' => AttributeResolver::value($this->label),
            'rules' => AttributeResolver::value($this->rules),
            'required' => AttributeResolver::value($this->required) ?? false,
            'type' => AttributeResolver::value($this->type),
            'view' => $this->view,
            'schema' => $this->schema,
            // allow subclasses to inject extra payload via override
        ];
    }

    public function resolve(array $context = []): static
    {
        $clone = clone $this;

        $clone->col = AttributeResolver::value($this->col, $context);
        $clone->default = AttributeResolver::value($this->default, $context);
        $clone->label = AttributeResolver::value($this->label, $context);
        $clone->rules = AttributeResolver::value($this->rules, $context);
        $clone->required = AttributeResolver::value($this->required, $context) ?? false;
        $clone->type = AttributeResolver::value($this->type, $context);
        $clone->schema = $this->serializeSchema($this->schema, $context);

        return $clone;
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
