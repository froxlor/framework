<?php

namespace Froxlor\Core\Services\Node\Provisioning;

class ScriptDeploymentPlan
{
    public function __construct(
        public readonly ScriptDefinition $definition,
        public readonly string $rendered,
        public readonly ?string $targetPath,
        public readonly bool $runAsRoot,
        public readonly ?array $ownership,
        public readonly bool $executable,
        public readonly bool $executeAfterWrite,
        public readonly array $reloadCommands,
    ) {
    }

    public function toArray(): array
    {
        return [
            'feature' => $this->definition->feature,
            'action' => $this->definition->action,
            'platform_key' => $this->definition->platformKey,
            'variant' => $this->definition->variant,
            'view' => $this->definition->view,
            'target_path' => $this->targetPath,
            'run_as_root' => $this->runAsRoot,
            'ownership' => $this->ownership,
            'executable' => $this->executable,
            'execute_after_write' => $this->executeAfterWrite,
            'reload_commands' => $this->reloadCommands,
            'rendered' => $this->rendered,
        ];
    }
}
