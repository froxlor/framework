<?php

namespace Froxlor\Core\Services\Environment\Jail;

use InvalidArgumentException;

final class JailPlan
{
    private array $binaries = [];

    private array $users = [];

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

    public function toArray(): array
    {
        $binaries = array_values($this->binaries);
        $users = $this->users;
        sort($binaries);
        ksort($users);

        return ['binaries' => $binaries, 'users' => $users];
    }

    private static function safePath(string $path): bool
    {
        return preg_match('#^/[a-zA-Z0-9_./+-]*$#D', $path)
            && ! in_array('..', explode('/', $path), true) && ! str_contains($path, '//');
    }
}
