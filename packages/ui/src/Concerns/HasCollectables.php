<?php

namespace Froxlor\UI\Concerns;

use Froxlor\UI\Contracts\Payloadable;
use Froxlor\UI\Contracts\Pushable;
use Froxlor\UI\Contracts\Resolvable;
use InvalidArgumentException;

trait HasCollectables
{
    /**
     * The components' array holds all pushed items, structured by component name and keys,
     * and the PushableInstance holds the actual item data and can have children.
     *
     * @var array<string, array<string, Pushable>> $collections
     */
    protected static array $collections = [];

    /**
     * Push items into a specific UI collection, it supports nested keys by using dot notation.
     *
     * @param string $collection The collection name, e.g. 'sidebar', 'navbar', 'user-navigation', 'settings'
     * @param Pushable[] $items The items to push can be an array of items
     */
    public static function push(string $collection, array $items = []): void
    {
        foreach ($items as $pushable) {
            if (!$pushable instanceof Pushable) {
                throw new InvalidArgumentException('Item must be an instance of Pushable');
            }

            // Check if required keys are set
            foreach ($pushable->requiredKeys as $key) {
                if (!isset($pushable->$key)) {
                    throw new InvalidArgumentException("Attribute {$key} is required for {$pushable->key}");
                }
            }

            // Handle nested keys and register the item
            $keys = explode('.', $pushable->key);
            $current = &static::$collections[$collection];
            foreach ($keys as $i => $key) {
                if ($i === count($keys) - 1) {
                    $current[$key] = $pushable;
                } else {
                    if (!isset($current[$key])) {
                        throw new InvalidArgumentException('Pushable "' . $pushable->key . '" requires a "' . $key . '" parent.');
                    }
                    // Find or create the child container
                    $children = &$current[$key]->children;
                    $current = &$children;
                }
            }
        }
    }

    /**
     * Get all pushed items for a specific collection or all items.
     *
     * @param string|null $collection Specify a collection to get items for
     * @return array<string, Pushable>
     */
    public static function stack(?string $collection = null): array
    {
        $collections = $collection
            ? static::$collections[$collection] ?? []
            : static::$collections;

        $map = function ($item) use (&$map) {
            if ($item instanceof Resolvable) {
                $item = $item->resolve();
            }
            if ($item instanceof Payloadable) {
                return (object)$item->toPayload();
            }
            if (is_array($item)) {
                return array_map($map, $item);
            }
            return $item;
        };

        return array_map($map, $collections);
    }
}
