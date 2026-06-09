<?php

namespace Froxlor\Core\Services\Node\Provisioning;

class ScriptDefinition
{
    public function __construct(
        public readonly string $feature,
        public readonly string $action,
        public readonly string $platformKey,
        public readonly string $view,
        public readonly ?string $variant = null,
        public readonly mixed $targetPath = null,
        public readonly bool $runAsRoot = false,
        public readonly ?array $ownership = null,
        public readonly bool $executable = false,
        public readonly bool $executeAfterWrite = false,
        public readonly mixed $reloadCommands = null,
        public readonly ?string $package = null,
    ) {
    }
}
