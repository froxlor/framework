<?php

namespace Froxlor\Core\Services\Node\Provisioning;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Platform\NodePlatform;
use Froxlor\Core\Services\Node\Platform\PlatformResolver;

class ScriptRegistry
{
    private static array $definitions = [];

    public static function register(ScriptDefinition $definition): void
    {
        self::$definitions[] = $definition;
    }

    public static function resolve(
        string $feature,
        string $action,
        Node|NodePlatform $target,
        ?string $variant = null,
    ): ?ScriptDefinition
    {
        $platform = $target instanceof Node
            ? app(PlatformResolver::class)->fromNode($target)
            : $target;

        $candidates = [
            $platform->key(),
            self::majorVersionKey($platform),
            $platform->id,
            $platform->family,
            '*',
        ];

        foreach ([$variant, null] as $variantCandidate) {
            foreach (array_filter($candidates) as $candidate) {
                foreach (self::$definitions as $definition) {
                    if ($definition->feature === $feature
                        && $definition->action === $action
                        && $definition->platformKey === $candidate
                        && $definition->variant === $variantCandidate) {
                        return $definition;
                    }
                }
            }
        }

        return null;
    }

    private static function majorVersionKey(NodePlatform $platform): ?string
    {
        if ($platform->versionId === '') {
            return null;
        }

        $major = explode('.', $platform->versionId)[0];

        return $major !== '' ? $platform->id . '@' . $major : null;
    }
}
