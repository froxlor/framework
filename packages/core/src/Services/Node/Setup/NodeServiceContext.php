<?php

namespace Froxlor\Core\Services\Node\Setup;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Platform\NodePlatform;
use InvalidArgumentException;

/** Read-only planning inputs. Providers do not receive an infrastructure adapter. */
final readonly class NodeServiceContext
{
    public function __construct(
        public string $nodeId,
        public NodePlatform $platform,
        public ServiceSettings $settings,
    ) {}

    public static function forNode(Node $node, NodeServiceProvider $provider, array $overrides = []): self
    {
        $platform = $node->platform();
        if (! $platform->supported || ! in_array($platform->key(), $provider->platforms(), true)) {
            throw new InvalidArgumentException('Node service does not support this platform.');
        }

        return new self((string) $node->getKey(), $platform, ServiceSettings::resolve($node, $provider, $overrides));
    }

    /** Resolve a package view without platform or provider fallback. */
    public function platformTemplate(string $namespace, string $name): string
    {
        return $namespace.'::node.services.'.$this->platform->id.'-'
            .str_replace('.', '-', $this->platform->versionId).'.'.$name;
    }
}
