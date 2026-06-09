<?php

namespace Froxlor\Core\Services\Node\Adapter;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Exceptions\NodeException;

abstract class Adapter
{
    public static string $name;

    public function __construct(protected Node $node)
    {
    }

    /**
     * Execute a command on the node server.
     *
     * @throws NodeException
     */
    abstract public function exec(string|array $command): bool|string;

    /**
     * Check if the node is connected.
     *
     * @throws NodeException
     */
    abstract public function isConnected(): bool;

    /**
     * Store a file on the node filesystem.
     *
     * @throws NodeException
     */
    abstract public function storagePut(string $remote, string $data): bool;

    /**
     * Get a file from the node filesystem.
     *
     * @throws NodeException
     */
    abstract public function storageGet(string $remote, bool|string $local = false): bool|string;

    /**
     * Delete a file from the node filesystem.
     *
     * @throws NodeException
     */
    abstract public function storageDelete(string $remote): bool;

    /**
     * Check if a file exists on the node filesystem.
     *
     * @throws NodeException
     */
    abstract public function storageExists(string $remote): bool;

    /**
     * Store a file on the node filesystem as root.
     *
     * @throws NodeException
     */
    abstract public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool;
}
