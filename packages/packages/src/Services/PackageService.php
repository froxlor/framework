<?php

namespace Froxlor\Packages\Services;

use Exception;
use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Packages\Models\Repository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class PackageService
{
    private function process(...$command): Process
    {
        $process = new Process(['composer', ...$command]);
        $process->setEnv(['COMPOSER_HOME' => base_path(), 'COMPOSER_AUTH' => $this->authJson()]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->run();

        return $process;
    }

    public function requirePackage(string $package): array
    {
        $process = $this->process('require', '--no-ansi', '--no-interaction', '--no-progress', $this->resolvePackageRequirement($package));

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Package ' . $package . ' has been installed successfully.'
        ];
    }

    public function updatePackage(?string $package = null): array
    {
        $process = $this->process('update', '--no-ansi', '--no-interaction', '--no-progress', $package);

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        return [
            'status' => 'success',
            'message' => $package
                ? 'Package ' . $package . ' has been updated successfully.'
                : 'All packages have been updated successfully.'
        ];
    }

    public function removePackage(string $package): array
    {
        $process = $this->process('remove', '--no-ansi', '--no-interaction', '--no-progress', $package);

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Package ' . $package . ' has been removed successfully.'
        ];
    }

    public function updateRepositories(): array
    {
        try {
            foreach (Repository::query()->get() as $repository) {
                if ($repository->enabled) {
                    $this->addRepository($repository);
                } else {
                    $this->removeRepository($repository);
                }
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        return [
            'status' => 'success',
            'message' => trans('froxlor-packages::generic.repositories_updated_successfully')
        ];
    }

    /**
     * @throws Exception
     */
    public function addRepository(Repository $repository): void
    {
        $process = $this->process('config', 'repositories.' . $repository->name, json_encode([
            'type' => $repository->type,
            'url' => $repository->url,
            ...$repository->options ? ['options' => $repository->options] : [],
        ]));

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }
    }

    /**
     * @throws Exception
     */
    public function removeRepository(Repository $repository): array
    {
        $process = $this->process('config', '--unset', 'repositories.' . $repository->name);

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }

        return [
            'status' => 'success',
            'message' => trans('froxlor-packages::generic.repository_deleted_successfully')
        ];
    }

    public function authJson(): ?string
    {
        $auth = Repository::query()->whereNotNull('auth')->get()->pluck('auth')->reduce(function ($carry, $item) {
            return array_merge_recursive($carry ?? [], $item);
        });

        return $auth ? json_encode($auth) : null;
    }

    public function packages(): array
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        $found = [];

        foreach ($packages as $package) {
            if (isset($package['extra']['froxlor']['type']) && $package['extra']['froxlor']['type'] === 'package') {
                $found[] = [
                    'id' => str_replace('/', ':', $package['name']),
                    'name' => $package['name'],
                    'installed' => true,
                    'version' => $package['version'],
                    'description' => $package['description'] ?? null,
                    'homepage' => $package['homepage'] ?? null,
                    'authors' => $package['authors'] ?? null,
                    'license' => $package['license'] ?? null,
                    'dependant' => $this->findDependant($package['name']) ?? null,
                    'depends' => $package['require'] ?? null,
                ];
            }
        }

        return $found;
    }

    public function findDependant(string $package): array
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        $found = [];

        foreach ($packages as $p) {
            if (isset($p['extra']['froxlor']['type']) && $p['extra']['froxlor']['type'] === 'package') {
                if (isset($p['require']) && is_array($p['require'])) {
                    foreach ($p['require'] as $dep => $version) {
                        if (str_starts_with($dep, $package)) {
                            $found[$dep] = $version;
                        }
                    }
                }
            }
        }

        return $found;
    }

    public function availablePackages(): array
    {
        return Cache::remember('packages', 300, function () {
            $client = new Client([
                'base_uri' => config('packages.discovery'),
                'timeout'  => 10.0,
                'verify'   => true,
            ]);

            $options = [
                'headers' => [
                    'User-Agent' => FroxlorVersion::userAgent(),
                ],
            ];

            if (config('packages.token')) {
                $options['auth'] = ['developers', config('packages.token')];
            }

            try {
                $response = $client->get('/packages.json', $options);

                $root = json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                // TODO: Show error message
                return [];
            }

            $packages = [];

            if (!is_array($root)) {
                // TODO: Show error message
                return [];
            }

            foreach ($root['includes'] as $include => $meta) {
                $response = $client->get($include, $options);
                $json = json_decode($response->getBody()->getContents(), true);

                if (!isset($json['packages'])) {
                    continue;
                }

                foreach ($json['packages'] as $name => $versions) {
                    $packages[$name] = $versions;
                }
            }

            $installedPackages = array_column($this->packages(), 'name');
            $installedMap = array_fill_keys($installedPackages, true);
            $result = [];

            foreach ($packages as $name => $versions) {
                $latest = reset($versions);
                $result[] = array_merge($latest, [
                    'id' => str_replace('/', ':', $name),
                    'installed' => isset($installedMap[$name]),
                ]);
            }

            return $result;
        });
    }

    public function availableUpdates(): array
    {
        $availablePackages = [];

        foreach ($this->availablePackages() as $package) {
            if (!isset($package['name'])) {
                continue;
            }

            $availablePackages[$package['name']] = $package;
        }

        $updates = [];

        foreach ($this->packages() as $installedPackage) {
            $availablePackage = $availablePackages[$installedPackage['name']] ?? null;

            if (!$availablePackage || !isset($availablePackage['version'])) {
                continue;
            }

            if (!$this->isNewerVersion($availablePackage['version'], $installedPackage['version'])) {
                continue;
            }

            $updates[] = array_merge($installedPackage, [
                'latest_version' => $availablePackage['version'],
                'latest_description' => $availablePackage['description'] ?? null,
            ]);
        }

        return $updates;
    }

    public function hasAvailableUpdates(): bool
    {
        return $this->availableUpdates() !== [];
    }

    private function isNewerVersion(string $availableVersion, string $installedVersion): bool
    {
        return version_compare(
            ltrim($availableVersion, 'v'),
            ltrim($installedVersion, 'v'),
            '>'
        );
    }

    private function resolvePackageRequirement(string $package): string
    {
        if ($this->hasVersionConstraint($package)) {
            return $package;
        }

        foreach ($this->availablePackages() as $availablePackage) {
            if (($availablePackage['name'] ?? null) !== $package) {
                continue;
            }

            if (!isset($availablePackage['version'])) {
                return $package;
            }

            return $package . ':' . $availablePackage['version'];
        }

        return $package;
    }

    private function hasVersionConstraint(string $package): bool
    {
        return str_contains($package, ':');
    }

    public function changeToDefaultRepository(): array
    {
        Repository::query()->where('name', 'froxlor')->update(['enabled' => true]);

        Repository::query()->whereNot('name', 'froxlor')->update(['enabled' => false]);

        return $this->updateRepositories();
    }

    public function changeToLocalRepository(): array
    {
        $repositories = explode(',', config('dev.repositories'));

        foreach ($repositories as $repoName) {
            $repository = Repository::query()
                ->updateOrCreate(
                    ['name' => $repoName],
                    [
                        'type' => 'path',
                        'url' => sprintf('../%s', $repoName),
                        'enabled' => true,
                    ]
                );

            $repository->update([
                'options' => [
                    'reference' => 'config',
                    'symlink' => true,
                ],
            ]);
        }

        Repository::query()->where('name', 'froxlor')->update(['enabled' => false]);

        return $this->updateRepositories();
    }
}
