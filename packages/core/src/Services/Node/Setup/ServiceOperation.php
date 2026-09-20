<?php

namespace Froxlor\Core\Services\Node\Setup;

use InvalidArgumentException;

/** Fixed operation vocabulary. Only trusted provider code constructs operations. */
final readonly class ServiceOperation
{
    private function __construct(public string $type, private array $parameters) {}

    /** @param list<string> $packages */
    public static function packages(array $packages): self
    {
        if ($packages === []) {
            throw new InvalidArgumentException('Package list cannot be empty.');
        }
        foreach ($packages as $package) {
            if (! is_string($package) || ! preg_match('/^[a-z0-9][a-z0-9+.-]+$/D', $package)) {
                throw new InvalidArgumentException('Invalid package name.');
            }
        }
        $packages = array_values(array_unique($packages));
        sort($packages);

        return new self('packages', compact('packages'));
    }

    /** Managed files must be within a root-owned configuration directory. */
    public static function config(string $path, string $content, string $mode = '0644'): self
    {
        if (! preg_match('~^/etc/[a-zA-Z0-9_./-]+$~D', $path)
            || str_contains($path, '//') || preg_match('~(^|/)\.{1,2}(/|$)~', $path)
            || str_ends_with($path, '/') || ! in_array($mode, ['0600', '0640', '0644'], true)) {
            throw new InvalidArgumentException('Invalid managed configuration path or permissions.');
        }

        return new self('config', compact('path', 'content', 'mode'));
    }

    /** @param list<string> $argv Explicit arguments, never a shell command string. */
    public static function command(string $type, array $argv): self
    {
        if (! in_array($type, ['validate', 'health'], true) || ! array_is_list($argv) || $argv === []
            || ! is_string($argv[0]) || ! str_starts_with($argv[0], '/')) {
            throw new InvalidArgumentException('Checks require an absolute executable and argument list.');
        }
        foreach ($argv as $argument) {
            if (! is_string($argument) || str_contains($argument, "\0")) {
                throw new InvalidArgumentException('Invalid command argument.');
            }
        }

        return new self($type, compact('argv'));
    }

    public static function service(string $name, string $onChange = 'reload'): self
    {
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.@-]*$/D', $name)
            || ! in_array($onChange, ['reload', 'restart'], true)) {
            throw new InvalidArgumentException('Invalid service definition.');
        }

        return new self('service', compact('name', 'onChange'));
    }

    /** Execution-only payload: do not log, serialize to queues or return through an API. */
    public function payload(): array
    {
        return $this->parameters;
    }

    /** Safe plan preview: contents and command arguments are deliberately omitted. */
    public function toArray(): array
    {
        $parameters = $this->parameters;
        unset($parameters['content'], $parameters['argv']);

        return ['type' => $this->type] + $parameters;
    }
}
