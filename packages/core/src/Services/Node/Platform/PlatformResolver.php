<?php

namespace Froxlor\Core\Services\Node\Platform;

use Froxlor\Core\Models\Node;

class PlatformResolver
{
    private const SUPPORTED = [
        'debian' => [
            'family' => 'debian',
            'versions' => [
                '13' => 'trixie',
            ],
        ],
        'ubuntu' => [
            'family' => 'debian',
            'versions' => [
                '24.04' => 'noble',
            ],
        ],
    ];

    public function fromNode(Node $node): NodePlatform
    {
        return $this->fromProperties($node->properties ?? []);
    }

    public function fromProperties(array $properties): NodePlatform
    {
        $os = (array) data_get($properties, 'os', []);

        return $this->fromOsRelease([
            'id' => (string) ($os['id'] ?? ''),
            'version_id' => (string) ($os['version_id'] ?? ''),
            'version_codename' => (string) ($os['codename'] ?? ''),
            'pretty_name' => (string) ($os['pretty_name'] ?? data_get($properties, 'sys', '')),
        ]);
    }

    public function fromOsRelease(array $osRelease): NodePlatform
    {
        $id = strtolower((string) ($osRelease['id'] ?? 'unknown'));
        $versionId = (string) ($osRelease['version_id'] ?? '');
        $codename = $this->normalizeNullableString(
            $osRelease['version_codename'] ?? $osRelease['ubuntu_codename'] ?? null
        );
        $prettyName = (string) ($osRelease['pretty_name'] ?? $osRelease['name'] ?? '');

        $definition = self::SUPPORTED[$id] ?? null;
        $family = $definition['family'] ?? $id;
        $expectedCodename = $definition['versions'][$versionId] ?? null;
        $supported = $expectedCodename !== null
            && ($codename === null || strtolower($expectedCodename) === strtolower($codename));

        return new NodePlatform(
            id: $id,
            family: $family,
            versionId: $versionId,
            codename: $codename,
            prettyName: $prettyName,
            supported: $supported,
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? strtolower($value) : null;
    }
}
