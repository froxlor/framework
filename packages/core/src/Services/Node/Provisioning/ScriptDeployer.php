<?php

namespace Froxlor\Core\Services\Node\Provisioning;

use Froxlor\Core\Models\Node;
use RuntimeException;

class ScriptDeployer
{
    public function plan(ScriptDefinition $definition, array $context = []): ScriptDeploymentPlan
    {
        $rendered = view($definition->view, $context)->render();
        $targetPath = $this->resolveValue($definition->targetPath, $context);
        $reloadCommands = $this->normalizeCommands($this->resolveValue($definition->reloadCommands, $context));

        return new ScriptDeploymentPlan(
            definition: $definition,
            rendered: $rendered,
            targetPath: is_string($targetPath) && $targetPath !== '' ? $targetPath : null,
            runAsRoot: $definition->runAsRoot,
            ownership: $definition->ownership,
            executable: $definition->executable,
            executeAfterWrite: $definition->executeAfterWrite,
            reloadCommands: $reloadCommands,
        );
    }

    public function apply(Node $node, ScriptDeploymentPlan $plan): void
    {
        if ($plan->targetPath === null) {
            throw new RuntimeException('Script deployment plan has no target path.');
        }

        $adapter = $node->adapter();
        $directory = dirname($plan->targetPath);

        if ($directory !== '' && $directory !== '.' && ! $adapter->storageExists($directory)) {
            if ($adapter->exec(['mkdir -p ' . escapeshellarg($directory)]) === false) {
                throw new RuntimeException('Unable to create deployment directory: ' . $directory);
            }
        }

        $written = $plan->runAsRoot
            ? $adapter->storagePutAsRoot($plan->targetPath, $plan->rendered, $plan->ownership ?? [])
            : $adapter->storagePut($plan->targetPath, $plan->rendered);

        if (! $written) {
            throw new RuntimeException('Unable to write deployment target: ' . $plan->targetPath);
        }

        $commands = [];

        if ($plan->executable) {
            $commands[] = 'chmod +x ' . escapeshellarg($plan->targetPath);
        }

        if ($plan->executeAfterWrite) {
            $commands[] = escapeshellarg($plan->targetPath);
        }

        foreach ($plan->reloadCommands as $command) {
            $commands[] = $command;
        }

        if ($commands !== [] && $adapter->exec($commands) === false) {
            throw new RuntimeException('Unable to execute deployment commands for: ' . $plan->targetPath);
        }
    }

    private function resolveValue(mixed $value, array $context = []): mixed
    {
        if ($value instanceof \Closure) {
            return app()->call($value, $context);
        }

        return $value;
    }

    private function normalizeCommands(mixed $commands): array
    {
        if ($commands === null || $commands === '') {
            return [];
        }

        return is_array($commands) ? array_values($commands) : [$commands];
    }
}
