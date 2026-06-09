<?php

namespace Froxlor\Core\Services\Node\Adapter;

class Local extends Adapter
{
    public static string $name = 'froxlor-core::generic.local';

    public function exec(array|string $command): bool|string
    {
        $output = [];
        $returnCode = 0;

        exec(
            "nsenter --target 1 --mount --uts --ipc --net --pid -- " . $this->getExecuteCommand($command),
            $output,
            $returnCode
        );

        return $returnCode === 0 ? implode("\n", $output) : false;
    }

    /**
     * Build bash heredoc execution wrapper.
     */
    private function getExecuteCommand(array|string $command): string
    {
        $commands = $this->wrapArray($command);

        $commandString = implode(PHP_EOL, $commands);

        $delimiter = 'EOF-SSH';

        return "bash -se << \\$delimiter" . PHP_EOL
            . $commandString . PHP_EOL
            . $delimiter;
    }

    private function wrapArray(string|array $command): array
    {
        return (array)$command;
    }

    public function isConnected(): bool
    {
        return $this->exec('uptime') !== false;
    }

    public function storagePut(string $remote, string $data): bool
    {
        return $this->storagePutAsRoot($remote, $data, [$this->node->username, $this->node->username]);
    }

    public function storageGet(string $remote, bool|string $local = false): bool|string
    {
        $cmd = "cat " . escapeshellarg($remote);

        $result = $this->exec($cmd);

        if ($result === false) {
            return false;
        }

        if ($local !== false) {
            file_put_contents($local, $result);
            return true;
        }

        return $result;
    }

    public function storageDelete(string $remote): bool
    {
        // Prevent accidental deletion of root directory
        if ($remote === '/' || empty($remote)) {
            return false;
        }

        $cmd = "rm -f " . escapeshellarg($remote);

        return $this->exec($cmd) !== false;
    }

    public function storageExists(string $remote): bool
    {
        $cmd = "[ -e " . escapeshellarg($remote) . " ] && echo 1 || echo 0";

        return trim((string)$this->exec($cmd)) === '1';
    }

    public function storagePutAsRoot(string $remote, string $data, array $ownership = []): bool
    {
        $encoded = base64_encode($data);

        $commands = [
            "echo " . escapeshellarg($encoded) . " | base64 --decode > " . escapeshellarg($remote)
        ];

        if (!empty($ownership) && count($ownership) == 2) {
            $commands[] = 'chown ' . escapeshellarg($ownership[0] . ':' . $ownership[1]) . ' ' . escapeshellarg($remote);
        }

        return $this->exec($commands) !== false;
    }
}
