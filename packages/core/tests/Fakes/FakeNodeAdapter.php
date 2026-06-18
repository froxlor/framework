<?php

namespace Tests\Fakes;

use Froxlor\Core\Services\Node\Adapter\Adapter;

class FakeNodeAdapter extends Adapter
{
    public static string $name = 'fake-node-adapter';

    public function exec(string|array $command): bool|string
    {
        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function storagePut(string $remote, string $data): bool
    {
        return true;
    }

    public function storageGet(string $remote, bool|string $local = false): bool|string
    {
        return '';
    }

    public function storageDelete(string $remote): bool
    {
        return true;
    }

    public function storageExists(string $remote): bool
    {
        return true;
    }

    public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool
    {
        return true;
    }
}
