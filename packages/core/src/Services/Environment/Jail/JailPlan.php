<?php

namespace Froxlor\Core\Services\Environment\Jail;

use InvalidArgumentException;

final class JailPlan
{
    private array $binaries = [];

    private array $users = [];

    private array $files = [];

    private array $directories = [];

    private array $environment = [];

    /** Host binary at the same absolute path in the jail; dependencies use Jailkit. */
    public function binary(string $path): self
    {
        if (! preg_match('#^/(?:usr/)?s?bin/[a-zA-Z0-9_+.-]+$#D', $path)
            || in_array(basename($path), ['.', '..'], true)) {
            throw new InvalidArgumentException('Jail binaries must be absolute executable paths in system bin directories.');
        }
        $this->binaries[$path] = $path;

        return $this;
    }

    /** Jail-only identity; no host account, password, home creation or recursive chown. */
    public function user(string $name, int $uid, int $gid, string $home = '/', string $shell = '/usr/sbin/nologin'): self
    {
        if (! preg_match('/^[a-z_][a-z0-9_-]{0,30}$/D', $name) || $name === 'root'
            || $uid < 1 || $gid < 1 || $uid > 2147483647 || $gid > 2147483647
            || ! self::safePath($home) || ! self::safePath($shell)) {
            throw new InvalidArgumentException('Invalid jail user definition.');
        }
        $definition = compact('name', 'uid', 'gid', 'home', 'shell');
        if (isset($this->users[$name]) && $this->users[$name] !== $definition) {
            throw new InvalidArgumentException('Conflicting jail user definition.');
        }
        $this->users[$name] = $definition;

        return $this;
    }

    public function file(string $path, string $content, int $mode = 0644): self
    {
        $this->validatePath($path);
        if ($mode < 0 || $mode > 0777 || str_contains($content, "\0")) {
            throw new InvalidArgumentException('Invalid jail file definition.');
        }
        $definition = ['content' => $content, 'mode' => $mode];
        if (isset($this->files[$path]) && $this->files[$path] !== $definition) {
            throw new InvalidArgumentException('Conflicting jail file definition.');
        }
        $this->files[$path] = $definition;
        return $this;
    }

    public function directory(string $path, int $mode = 0755): self
    {
        $this->validatePath($path);
        if ($mode < 0 || $mode > 0777) {
            throw new InvalidArgumentException('Invalid jail directory definition.');
        }
        if (isset($this->directories[$path]) && $this->directories[$path] !== $mode) {
            throw new InvalidArgumentException('Conflicting jail directory definition.');
        }
        $this->directories[$path] = $mode;
        return $this;
    }

    public function environment(string $name, string $value): self
    {
        if (! preg_match('/^[A-Z_][A-Z0-9_]{0,127}$/D', $name) || str_contains($value, "\0") || str_contains($value, "\n")) {
            throw new InvalidArgumentException('Invalid jail environment variable.');
        }
        if (isset($this->environment[$name]) && $this->environment[$name] !== $value) {
            throw new InvalidArgumentException('Conflicting jail environment variable.');
        }
        $this->environment[$name] = $value;
        return $this;
    }

    public function toArray(): array
    {
        $binaries = array_values($this->binaries);
        $users = $this->users;
        sort($binaries);
        ksort($users);
        ksort($this->files);
        ksort($this->directories);
        ksort($this->environment);

        return ['binaries' => $binaries, 'users' => $users, 'files' => $this->files, 'directories' => $this->directories, 'environment' => $this->environment];
    }

    private static function safePath(string $path): bool
    {
        return preg_match('#^/[a-zA-Z0-9_./+-]*$#D', $path)
            && ! in_array('..', explode('/', $path), true) && ! str_contains($path, '//');
    }

    private function validatePath(string $path): void
    {
        if (! self::safePath($path) || $path === '/' || str_starts_with($path, '/.froxlor')
            || in_array($path, ['/etc/passwd', '/etc/group', '/etc/shadow', '/etc/gshadow'], true)) {
            throw new InvalidArgumentException('Invalid jail-managed path.');
        }
    }
}
