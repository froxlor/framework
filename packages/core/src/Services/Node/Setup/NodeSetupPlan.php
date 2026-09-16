<?php

namespace Froxlor\Core\Services\Node\Setup;

/** Immutable snapshot; never accepts a serialized client-supplied execution payload. */
final readonly class NodeSetupPlan
{
    /** @param array<string, array{role: string, revision: string, plan: ServicePlan}> $services */
    public function __construct(
        public string $nodeId,
        public string $platform,
        public array $services,
        private string $targetFingerprint = '',
    ) {}

    public function fingerprint(): string
    {
        $services = [];
        foreach ($this->services as $key => $service) {
            $services[$key] = [$service['role'], $service['revision'], $service['plan']->fingerprint()];
        }

        return hash('sha256', json_encode([$this->nodeId, $this->platform, $this->targetFingerprint, $services], JSON_THROW_ON_ERROR));
    }

    public function toArray(): array
    {
        $services = [];
        foreach ($this->services as $key => $service) {
            $services[$key] = [
                'role' => $service['role'],
                'revision' => $service['revision'],
                'operations' => $service['plan']->toArray(),
            ];
        }

        return ['node_id' => $this->nodeId, 'platform' => $this->platform,
            'fingerprint' => $this->fingerprint(), 'services' => $services];
    }
}
