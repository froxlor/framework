<?php

namespace Froxlor\Core\Http\Controllers\Concerns;

use Froxlor\Core\Models\Node;

trait NormalizesNodeProperties
{
    /**
     * Move API-only node fields into the persisted properties array.
     *
     * The remote adapter reads SSH credentials from properties.ssh_key. Keeping
     * the public request field flat avoids exposing the storage shape while
     * still preserving existing explored node properties during updates.
     *
     * @param array<string, mixed> $nodeData
     * @return array<string, mixed>
     */
    protected function normalizeNodeProperties(array $nodeData, ?Node $node = null): array
    {
        if (!array_key_exists('ssh_key', $nodeData)) {
            return $nodeData;
        }

        $properties = $node?->properties ?? [];
        $properties['ssh_key'] = $nodeData['ssh_key'];
        $nodeData['properties'] = $properties;
        unset($nodeData['ssh_key']);

        return $nodeData;
    }
}
