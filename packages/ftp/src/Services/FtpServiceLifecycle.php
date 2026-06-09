<?php

namespace Froxlor\Ftp\Services;

use Froxlor\Core\Services\Node\Provisioning\ScriptDeployer;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Ftp\Models\FtpService;
use Throwable;

class FtpServiceLifecycle
{
    public function __construct(private readonly ScriptDeployer $deployer)
    {
    }

    public function install(FtpService $ftpService): void
    {
        $ftpService->forceFill([
            'status' => 'installing',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $ftpService->node->adapter();
            $platform = $ftpService->node->platform();
            $definition = ScriptRegistry::resolve('ftp-service', 'install', $ftpService->node, $ftpService->driver);

            if (! $platform->supported) {
                $this->fail($ftpService, 'Unsupported platform: ' . $platform->key());
                return;
            }

            if (! $definition) {
                $this->fail($ftpService, 'No install script registered for platform ' . $platform->key() . ' and driver ' . $ftpService->driver);
                return;
            }

            if (! $adapter->isConnected()) {
                $this->fail($ftpService, 'Unable to connect to node.');
                return;
            }

            $probe = $this->binaryProbeCommand($ftpService->driver);
            $version = trim((string) $adapter->exec([$probe]));
            $properties = $ftpService->properties ?? [];
            $properties['lifecycle']['install_probe'] = $probe;
            $properties['lifecycle']['install_result'] = $version;
            $properties['lifecycle']['install_script_view'] = $definition->view;
            $installPlan = $this->deployer->plan($definition, [
                'ftpService' => $ftpService,
                'node' => $ftpService->node,
                'platform' => $platform,
            ]);
            $properties['lifecycle']['install_plan'] = $installPlan->toArray();

            if ($version !== '') {
                $ftpService->forceFill([
                    'installed_at' => now(),
                    'status' => 'installed',
                    'properties' => $properties,
                    'last_error' => null,
                ])->save();

                return;
            }

            $ftpService->forceFill([
                'status' => 'install_pending',
                'properties' => $properties,
                'last_error' => 'FTP service binary was not found. Review install deployment plan.',
            ])->save();
        } catch (Throwable $exception) {
            $this->fail($ftpService, $exception->getMessage());
        }
    }

    public function configure(FtpService $ftpService): void
    {
        $ftpService->forceFill([
            'status' => 'configuring',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $ftpService->node->adapter();
            $platform = $ftpService->node->platform();
            $definition = ScriptRegistry::resolve('ftp-service', 'configure', $ftpService->node, $ftpService->driver);

            if (! $platform->supported) {
                $this->fail($ftpService, 'Unsupported platform: ' . $platform->key());
                return;
            }

            if (! $definition) {
                $this->fail($ftpService, 'No configure script registered for platform ' . $platform->key() . ' and driver ' . $ftpService->driver);
                return;
            }

            if (! $adapter->isConnected()) {
                $this->fail($ftpService, 'Unable to connect to node.');
                return;
            }

            if ($ftpService->passive_min_port > $ftpService->passive_max_port) {
                $this->fail($ftpService, 'Passive port range is invalid.');
                return;
            }

            $properties = $ftpService->properties ?? [];
            $properties['lifecycle']['configured_listen_address'] = $ftpService->listen_address;
            $properties['lifecycle']['configured_port'] = $ftpService->port;
            $properties['lifecycle']['configured_passive_range'] = [
                $ftpService->passive_min_port,
                $ftpService->passive_max_port,
            ];
            $properties['lifecycle']['configure_script_view'] = $definition->view;
            $configurePlan = $this->deployer->plan($definition, [
                'ftpService' => $ftpService,
                'node' => $ftpService->node,
                'platform' => $platform,
            ]);
            $properties['lifecycle']['configure_plan'] = $configurePlan->toArray();

            $this->deployer->apply($ftpService->node, $configurePlan);

            $ftpService->forceFill([
                'configured_at' => now(),
                'status' => 'configured',
                'properties' => $properties,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->fail($ftpService, $exception->getMessage());
        }
    }

    public function check(FtpService $ftpService): void
    {
        $ftpService->forceFill([
            'status' => 'checking',
            'last_error' => null,
        ])->save();

        try {
            $adapter = $ftpService->node->adapter();

            if (! $adapter->isConnected()) {
                $ftpService->forceFill([
                    'status' => 'unreachable',
                    'is_reachable' => false,
                    'last_checked_at' => now(),
                    'last_error' => 'Unable to connect to node.',
                ])->save();
                return;
            }

            $serviceProbe = $this->serviceProbeCommand($ftpService->driver);
            $serviceState = trim((string) $adapter->exec([$serviceProbe]));
            $version = trim((string) $adapter->exec([$this->binaryProbeCommand($ftpService->driver)]));

            $properties = $ftpService->properties ?? [];
            $properties['lifecycle']['service_probe'] = $serviceProbe;
            $properties['lifecycle']['service_state'] = $serviceState;
            $properties['lifecycle']['version'] = $version;

            $isReachable = $serviceState === 'active' || $serviceState === 'running';

            $payload = [
                'status' => $isReachable ? 'ready' : 'unreachable',
                'is_reachable' => $isReachable,
                'last_checked_at' => now(),
                'properties' => $properties,
                'last_error' => $isReachable ? null : 'FTP service is not active on the node.',
            ];

            if ($version !== '' && ! $ftpService->installed_at) {
                $payload['installed_at'] = now();
            }

            $ftpService->forceFill($payload)->save();
        } catch (Throwable $exception) {
            $this->fail($ftpService, $exception->getMessage());
        }
    }

    private function fail(FtpService $ftpService, string $message): void
    {
        $ftpService->forceFill([
            'status' => 'error',
            'last_error' => $message,
        ])->save();
    }

    private function binaryProbeCommand(string $driver): string
    {
        return match ($driver) {
            'vsftpd' => 'vsftpd -version 2>&1 || vsftpd -v 2>&1 || true',
            default => 'true',
        };
    }

    private function serviceProbeCommand(string $driver): string
    {
        return match ($driver) {
            'vsftpd' => 'systemctl is-active vsftpd || true',
            default => 'true',
        };
    }
}
