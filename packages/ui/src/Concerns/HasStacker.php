<?php

namespace Froxlor\UI\Concerns;

trait HasStacker
{
    public static array $schemas = [];

    /**
     * Push a schema to the stack.
     */
    public static function stack(string $key, callable|array $schema): void
    {
        self::$schemas[$key][] = $schema;
    }

    /**
     * Load a schema from the stack.
     */
    public static function stacks(string $key): callable|array
    {
        return self::$schemas[$key] ?? [];
    }
}
