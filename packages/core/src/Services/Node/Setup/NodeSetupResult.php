<?php

namespace Froxlor\Core\Services\Node\Setup;

/** Safe result: no command output, settings or rendered configuration contents. */
final readonly class NodeSetupResult
{
    public function __construct(public string $runId, public string $fingerprint) {}
}
