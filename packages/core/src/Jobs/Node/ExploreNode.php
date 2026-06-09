<?php

namespace Froxlor\Core\Jobs\Node;

use Exception;
use Froxlor\Core\Events\Node\NodeExplored;
use Froxlor\Core\Events\Node\NodeExploreUpdate;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Platform\PlatformResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExploreNode implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Node $node, private readonly bool $initial = false)
    {
        //
    }

    /**
     * Create a new job instance.
     */
    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        $adapter = $this->node->adapter();

        // connect to node and fetch metrics, if initial: also guess IP addresses
        if ($adapter->isConnected()) {
            // get properties (cpu, ram, etc.)
            $osRelease = $this->readOsRelease($adapter);
            $platform = app(PlatformResolver::class)->fromOsRelease($osRelease);

            $properties = [
                'sys' => $platform->prettyName,
                'os' => $platform->toArray(),
                'kernel' => trim($adapter->exec(['uname -r'])),
                'cpu' => $this->getCpuProperties($adapter),
                'memory' => $this->getMemoryProperties($adapter),
                'disk' => $this->getDiskProperties($adapter),
                'disks' => $this->getDisksProperties($adapter),
            ];

            // set properties
            $this->node->properties = array_merge($this->node->properties ?? [], $properties);

            // set node "last_guid_number" according to the system usage
            $last_guid = $adapter->exec([<<<EOC
awk -F: -v min="$(grep '^UID_MIN' /etc/login.defs | awk '{print $2}')" -v max="$(grep '^UID_MAX' /etc/login.defs | awk '{print $2}')" '$3 >= min && $3 <= max {print $3}' /etc/passwd | sort -n | tail -1
EOC
            ]);
            if ((int)$last_guid > $this->node->latestGuid) {
                $this->node->setSetting('node.last_guid_number', (int)$last_guid);
            }

            // handle initial exploration
            if ($this->initial) {
                // get node ip addresses assigned to the system
                $ips = $adapter->exec(['hostname -I']);
                foreach (explode(" ", trim($ips)) as $ipaddr) {
                    $this->node->nodeInterfaces()->create([
                        'bind_addr' => trim($ipaddr),
                    ]);
                }
                $this->node->save();
                event(new NodeExplored($this->node));
            }

            // save properties
            if ($this->node->isDirty()) {
                $this->node->save();
                event(new NodeExploreUpdate($this->node));
            }
        } else {
            throw new Exception('Unable to connect to node');
        }
    }

    private function getCpuProperties(Adapter $adapter): array
    {
        return [
            'cores' => trim($adapter->exec(['nproc'])),
            'utilized' => $this->getCpuUsage($adapter),
        ];
    }

    private function getCpuUsage(Adapter $adapter): float
    {
        $stat1 = $adapter->exec(['cat /proc/stat']);
        usleep(100000); // 100ms
        $stat2 = $adapter->exec(['cat /proc/stat']);

        $cpu1 = preg_split('/\s+/', explode("\n", $stat1)[0]);
        $cpu2 = preg_split('/\s+/', explode("\n", $stat2)[0]);

        $idle1 = $cpu1[4];
        $idle2 = $cpu2[4];

        $total1 = array_sum(array_slice($cpu1, 1));
        $total2 = array_sum(array_slice($cpu2, 1));

        $totalDiff = $total2 - $total1;
        $idleDiff = $idle2 - $idle1;

        return $totalDiff > 0
            ? round((1 - ($idleDiff / $totalDiff)) * 100, 2)
            : 0;
    }

    private function getMemoryProperties(Adapter $adapter): array
    {
        $meminfoRaw = $adapter->exec(['cat /proc/meminfo']);
        $lines = explode("\n", trim($meminfoRaw));

        $data = [];
        foreach ($lines as $line) {
            [$key, $value] = explode(':', $line);
            $data[$key] = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        }

        return [
            'total' => $data['MemTotal'] * 1024,
            'free' => $data['MemFree'] * 1024,
            'available' => $data['MemAvailable'] * 1024,
            'utilized' => $data['MemTotal'] > 0
                ? round(($data['MemFree'] / $data['MemTotal']) * 100, 2)
                : 0,
        ];
    }

    private function getDiskProperties(Adapter $adapter): array
    {
        $disks = $this->getDisksProperties($adapter);

        $totalSize = array_sum(array_column($disks, 'size'));
        $totalUsed = array_sum(array_column($disks, 'used'));
        $totalFree = array_sum(array_column($disks, 'free'));

        return [
            'size' => $totalSize,
            'used' => $totalUsed,
            'free' => $totalFree,
            'utilized' => $totalSize > 0 ? round(($totalUsed / $totalSize) * 100, 2) : 0,
        ];
    }

    private function getDisksProperties(Adapter $adapter): array
    {
        $disks = [];
        $output = $adapter->exec(["df -B1 --output=source,size,used,avail,target | grep '^/dev/'"]);

        foreach (explode("\n", trim($output)) as $line) {
            if (empty($line)) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);

            $size = (int) ($parts[1] ?? 0);
            $used = (int) ($parts[2] ?? 0);
            $avail = (int) ($parts[3] ?? 0);

            $disks[] = [
                'dev' => $parts[0],
                'size' => $size,
                'used' => $used,
                'free' => $avail,
                'mount' => $parts[4] ?? '',
                'utilized' => $size > 0
                    ? round(($used / $size) * 100, 2)
                    : 0,
            ];
        }

        return $disks;
    }

    private function readOsRelease(Adapter $adapter): array
    {
        $output = (string) $adapter->exec(['cat /etc/os-release']);
        $data = [];

        foreach (explode("\n", trim($output)) as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $data[strtolower($key)] = trim($value, " \n\r\t\v\0\"");
        }

        return $data;
    }
}
