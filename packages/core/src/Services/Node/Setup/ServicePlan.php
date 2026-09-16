<?php

namespace Froxlor\Core\Services\Node\Setup;

use LogicException;

/** Immutable, side-effect-free provider plan. Execution uses canonical phases. */
final readonly class ServicePlan
{
    /** @param list<ServiceOperation> $operations */
    private function __construct(private array $operations = []) {}

    public static function make(): self
    {
        return new self;
    }

    public function ensurePackages(array $packages): self
    {
        return $this->append(ServiceOperation::packages($packages));
    }

    public function managedConfig(string $path, string $template, array $data = [], string $mode = '0644'): self
    {
        return $this->config($path, view($template, $data)->render(), $mode);
    }

    public function config(string $path, string $content, string $mode = '0644'): self
    {
        return $this->append(ServiceOperation::config($path, $content, $mode));
    }

    public function validateCommand(array $argv): self
    {
        return $this->append(ServiceOperation::command('validate', $argv));
    }

    /** Enable and start a service; reload/restart an active service only on changes. */
    public function activateService(string $name, string $onChange = 'reload'): self
    {
        return $this->append(ServiceOperation::service($name, $onChange));
    }

    public function healthCheck(array $argv): self
    {
        return $this->append(ServiceOperation::command('health', $argv));
    }

    /** @return list<ServiceOperation> */
    public function operations(): array
    {
        return $this->operations;
    }

    public function assertValid(): void
    {
        $types = array_map(fn (ServiceOperation $operation) => $operation->type, $this->operations);
        if (in_array('config', $types, true) && ! in_array('validate', $types, true)) {
            throw new LogicException('Managed configurations require a validation command.');
        }
        if (in_array('service', $types, true) && ! in_array('health', $types, true)) {
            throw new LogicException('Activated services require a health check.');
        }
    }

    public function toArray(): array
    {
        return array_map(fn (ServiceOperation $operation) => $operation->toArray(), $this->operations);
    }

    /** Hash includes rendered contents, but previews never expose them. */
    public function fingerprint(): string
    {
        return hash('sha256', json_encode(array_map(
            fn (ServiceOperation $operation) => [$operation->type, $operation->payload()],
            $this->operations,
        ), JSON_THROW_ON_ERROR));
    }

    private function append(ServiceOperation $operation): self
    {
        return new self([...$this->operations, $operation]);
    }
}
