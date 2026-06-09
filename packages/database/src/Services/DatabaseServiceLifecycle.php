<?php

namespace Froxlor\Database\Services;

use Froxlor\Core\Services\Node\Provisioning\ScriptDeployer;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Database\Models\DatabaseServer;
use Throwable;

class DatabaseServiceLifecycle
{
    public function __construct(private readonly ScriptDeployer $deployer)
    {
    }

    public function install(DatabaseServer $databaseServer): void
    {
        $databaseServer->forceFill([
            'status' => 'installing',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $databaseServer->node->adapter();
            $platform = $databaseServer->node->platform();
            $definition = ScriptRegistry::resolve('database-service', 'install', $databaseServer->node, $databaseServer->driver);

            if (! $platform->supported) {
                $this->fail($databaseServer, 'Unsupported platform: ' . $platform->key());
                return;
            }

            if (! $definition) {
                $this->fail($databaseServer, 'No install script registered for platform ' . $platform->key() . ' and driver ' . $databaseServer->driver);
                return;
            }

            if (! $adapter->isConnected()) {
                $this->fail($databaseServer, 'Unable to connect to node.');
                return;
            }

            $probe = $this->binaryProbeCommand($databaseServer->driver);
            $version = trim((string) $adapter->exec([$probe]));
            $properties = $databaseServer->properties ?? [];
            $properties['lifecycle']['install_probe'] = $probe;
            $properties['lifecycle']['install_result'] = $version;
            $properties['lifecycle']['install_script_view'] = $definition->view;
            $installPlan = $this->deployer->plan($definition, [
                'databaseServer' => $databaseServer,
                'node' => $databaseServer->node,
                'platform' => $platform,
            ]);
            $properties['lifecycle']['install_plan'] = $installPlan->toArray();

            if ($version !== '') {
                $databaseServer->forceFill([
                    'installed_at' => now(),
                    'status' => 'installed',
                    'properties' => $properties,
                    'last_error' => null,
                ])->save();

                return;
            }

            $databaseServer->forceFill([
                'status' => 'install_pending',
                'properties' => $properties,
                'last_error' => 'Database service binary was not found. Review install deployment plan.',
            ])->save();
        } catch (Throwable $exception) {
            $this->fail($databaseServer, $exception->getMessage());
        }
    }

    public function configure(DatabaseServer $databaseServer): void
    {
        $databaseServer->forceFill([
            'status' => 'configuring',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $databaseServer->node->adapter();
            $platform = $databaseServer->node->platform();
            $definition = ScriptRegistry::resolve('database-service', 'configure', $databaseServer->node, $databaseServer->driver);

            if (! $platform->supported) {
                $this->fail($databaseServer, 'Unsupported platform: ' . $platform->key());
                return;
            }

            if (! $definition) {
                $this->fail($databaseServer, 'No configure script registered for platform ' . $platform->key() . ' and driver ' . $databaseServer->driver);
                return;
            }

            if (! $adapter->isConnected()) {
                $this->fail($databaseServer, 'Unable to connect to node.');
                return;
            }

            if (! $databaseServer->host || ! $databaseServer->port) {
                $this->fail($databaseServer, 'Host and port are required for configuration.');
                return;
            }

            $properties = $databaseServer->properties ?? [];
            $properties['lifecycle']['configured_host'] = $databaseServer->host;
            $properties['lifecycle']['configured_port'] = $databaseServer->port;
            $properties['lifecycle']['admin_username'] = $databaseServer->admin_username;
            $properties['lifecycle']['configure_script_view'] = $definition->view;
            $configurePlan = $this->deployer->plan($definition, [
                'databaseServer' => $databaseServer,
                'node' => $databaseServer->node,
                'platform' => $platform,
            ]);
            $properties['lifecycle']['configure_plan'] = $configurePlan->toArray();

            $this->deployer->apply($databaseServer->node, $configurePlan);

            $databaseServer->forceFill([
                'configured_at' => now(),
                'status' => 'configured',
                'properties' => $properties,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->fail($databaseServer, $exception->getMessage());
        }
    }

    public function check(DatabaseServer $databaseServer): void
    {
        $databaseServer->forceFill([
            'status' => 'checking',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $databaseServer->node->adapter();

            if (! $adapter->isConnected()) {
                $databaseServer->forceFill([
                    'status' => 'unreachable',
                    'is_reachable' => false,
                    'last_checked_at' => now(),
                    'last_error' => 'Unable to connect to node.',
                ])->save();
                return;
            }

            $serviceProbe = $this->serviceProbeCommand($databaseServer->driver);
            $serviceState = trim((string) $adapter->exec([$serviceProbe]));
            $version = trim((string) $adapter->exec([$this->binaryProbeCommand($databaseServer->driver)]));

            $properties = $databaseServer->properties ?? [];
            $properties['lifecycle']['service_probe'] = $serviceProbe;
            $properties['lifecycle']['service_state'] = $serviceState;
            $properties['lifecycle']['version'] = $version;

            $isReachable = $serviceState === 'active' || $serviceState === 'running';

            $payload = [
                'status' => $isReachable ? 'ready' : 'unreachable',
                'is_reachable' => $isReachable,
                'last_checked_at' => now(),
                'properties' => $properties,
                'last_error' => $isReachable ? null : 'Database service is not active on the node.',
            ];

            if ($version !== '' && ! $databaseServer->installed_at) {
                $payload['installed_at'] = now();
            }

            $databaseServer->forceFill($payload)->save();
        } catch (Throwable $exception) {
            $this->fail($databaseServer, $exception->getMessage());
        }
    }

    private function fail(DatabaseServer $databaseServer, string $message): void
    {
        $databaseServer->forceFill([
            'status' => 'error',
            'last_error' => $message,
        ])->save();
    }

    private function binaryProbeCommand(string $driver): string
    {
        return match ($driver) {
            'pgsql' => 'psql --version || true',
            'mariadb' => 'mariadb --version || mysql --version || true',
            default => 'mysql --version || true',
        };
    }

    private function serviceProbeCommand(string $driver): string
    {
        return match ($driver) {
            'pgsql' => 'systemctl is-active postgresql || true',
            'mariadb' => 'systemctl is-active mariadb || true',
            default => 'systemctl is-active mysql || systemctl is-active mysqld || true',
        };
    }
}
