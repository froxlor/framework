<?php

namespace Froxlor\Core\Services\Node\Platform;

class NodePlatform
{
    public function __construct(
        public readonly string $id,
        public readonly string $family,
        public readonly string $versionId,
        public readonly ?string $codename,
        public readonly string $prettyName,
        public readonly bool $supported,
    ) {
    }

    public function key(): string
    {
        return $this->id . '@' . $this->versionId;
    }

    public function label(): string
    {
        return $this->prettyName !== '' ? $this->prettyName : strtoupper($this->id) . ' ' . $this->versionId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'family' => $this->family,
            'version_id' => $this->versionId,
            'codename' => $this->codename,
            'pretty_name' => $this->prettyName,
            'supported' => $this->supported,
            'key' => $this->key(),
        ];
    }
}
