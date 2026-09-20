<?php

namespace Froxlor\Core\Services\Environment\Jail;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Node;
use InvalidArgumentException;

/** Stable identity inputs. Providers may query their own package settings using these IDs. */
final readonly class JailContext
{
    public function __construct(
        public string $environmentId,
        public string $tenantId,
        public string $nodeId,
        public string $root,
        public string $user,
        public int $guid,
    ) {
        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/Di', $environmentId)
            || ! preg_match('/^[a-z_][a-z0-9_-]{0,30}$/D', $user) || $guid < 1 || $guid > 2147483647
            || ! preg_match('#^/(?:[a-zA-Z0-9_-][a-zA-Z0-9_.-]*/)+'.preg_quote($environmentId, '#').'$#D', $root)) {
            throw new InvalidArgumentException('Unsafe jail identity or root path.');
        }
    }

    public static function forEnvironment(Environment $environment, Node $node, string $user, int $guid, ?string $root = null): self
    {
        return new self($environment->id, $environment->tenant_id, $node->id,
            $root ?? rtrim($node->getSetting('node.basedir', '/var/environments'), '/').'/'.$environment->id,
            $user, $guid);
    }
}
