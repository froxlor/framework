<?php

namespace Froxlor\Packages\Services;

use Exception;
use Froxlor\Core\Support\ComposerPackage;
use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Packages\Support\MarketplaceCredentials;
use Froxlor\Packages\Support\SafeModeRegistry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class PackageService
{
    private const PROTECTED_REPOSITORY_URL = 'https://packages.froxlor.org';

    private const VERIFIED_REPOSITORY_URLS = [
        'https://packages.froxlor.org',
        'https://packages.froxlor.com',
        'https://packages.froxlor.dev',
    ];

    /**
     * Run a composer command in the application root and return the finished process.
     *
     * @param string ...$command composer arguments (e.g. 'require', '--no-interaction', 'vendor/name')
     */
    private function process(...$command): Process
    {
        // Composer reads auth.json from COMPOSER_HOME on its own, no need to pass credentials via env.
        $process = new Process(['composer', ...$command]);
        $process->setEnv(['COMPOSER_HOME' => base_path()]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->run();

        return $process;
    }

    /**
     * Install a package via composer require, then verify the app still boots and run the
     * post-install sync (migrations, install hooks).
     *
     * @param string $package package name, optionally with a version constraint ('vendor/name:^1.0')
     * @return array{status: string, message: string}
     */
    public function requirePackage(string $package): array
    {
        $before = $this->packageVersions();

        $process = $this->process('require', '--no-ansi', '--no-interaction', '--no-progress', $this->resolvePackageRequirement($package));

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        if ($failure = $this->quarantineIfBootFails()) {
            return $failure;
        }

        $this->syncAfterComposerChange($before);

        Cache::forget('packages');

        return [
            'status' => 'success',
            'message' => 'Package ' . $package . ' has been installed successfully.'
        ];
    }

    /**
     * Update a single package (or all packages when none is given) via composer update, then
     * verify the app still boots and run the post-update sync (migrations, update hooks).
     *
     * @param string|null $package package name, or null to update everything
     * @param bool $interactive attach the sync subprocess to the current TTY so update hooks can prompt
     * @return array{status: string, message: string}
     */
    public function updatePackage(?string $package = null, bool $interactive = false): array
    {
        $before = $this->packageVersions();

        $process = $this->process('update', '--no-ansi', '--no-interaction', '--no-progress', $package);

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        if ($failure = $this->quarantineIfBootFails()) {
            return $failure;
        }

        $this->syncAfterComposerChange($before, $interactive);

        Cache::forget('packages');

        return [
            'status' => 'success',
            'message' => $package
                ? 'Package ' . $package . ' has been updated successfully.'
                : 'All packages have been updated successfully.'
        ];
    }

    /**
     * @return array<string, string> package name => version
     */
    private function packageVersions(): array
    {
        return array_column($this->packages(), 'version', 'name');
    }

    /**
     * Runs migrations and fires installing/installed or updating/updated hooks for whatever
     * changed in this composer operation, batching everything into a single fresh subprocess
     * (froxlor:packages:sync) since a just-installed/updated package's code isn't autoloadable
     * in the process that shelled out to composer.
     *
     * When $interactive is true (the caller is itself an artisan command running in a real
     * terminal — see PackagesUpdate), the subprocess's TTY is attached so a hook that needs
     * admin input (e.g. via Laravel Prompts) can genuinely block and ask for it there, instead of
     * only ever being able to fall back to PackageServiceProvider::requireCompletion().
     */
    private function syncAfterComposerChange(array $before, bool $interactive = false): void
    {
        $after = $this->packageVersions();

        $installed = array_keys(array_diff_key($after, $before));
        $updated = [];

        foreach (array_intersect_key($after, $before) as $name => $version) {
            if ($version !== $before[$name]) {
                $updated[] = $name;
            }
        }

        if ($installed === [] && $updated === []) {
            return;
        }

        $arguments = [];

        foreach ($installed as $name) {
            $arguments[] = '--installed=' . $name;
        }

        foreach ($updated as $name) {
            $arguments[] = '--updated=' . $name;
        }

        $process = new Process(['php', 'artisan', 'froxlor:packages:sync', ...$arguments]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);

        if ($interactive && Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->run();
    }

    /**
     * Force a fresh, isolated boot of the application after a composer change and, if it now
     * crashes, figure out which package caused it and soft-disable that package so the *next*
     * real request isn't the one that discovers the crash.
     */
    private function quarantineIfBootFails(): ?array
    {
        $verification = $this->verifyBoot();

        if ($verification['status'] === 'ok') {
            return null;
        }

        $offendingPackage = $verification['file'] ? $this->findPackageForPath($verification['file']) : null;

        if (!$offendingPackage) {
            return [
                'status' => 'error',
                'message' => $verification['message'],
            ];
        }

        app(SafeModeRegistry::class)->disable($offendingPackage, $verification['message'], auto: true);

        return [
            'status' => 'error',
            'message' => trans('froxlor-packages::generic.package_auto_disabled', [
                'package' => $offendingPackage,
                'reason' => $verification['message'],
            ]),
        ];
    }

    /**
     * Boot the application in a fresh subprocess so a provider crash doesn't take down whatever
     * is currently running (e.g. the request serving the Packages UI).
     *
     * @return array{status: string, file?: string|null, message?: string}
     */
    public function verifyBoot(): array
    {
        $script = __DIR__ . '/../../resources/scripts/verify-boot.php';

        $process = new Process(['php', $script]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            return ['status' => 'ok'];
        }

        $result = json_decode($process->getErrorOutput(), true);

        return [
            'status' => 'error',
            'file' => $result['file'] ?? null,
            'message' => $result['message'] ?? $process->getErrorOutput(),
        ];
    }

    /**
     * Uninstall a package via composer remove, refusing when other installed froxlor packages
     * still depend on it.
     *
     * @return array{status: string, message: string}
     */
    public function removePackage(string $package): array
    {
        $dependants = $this->findDependant($package);

        if ($dependants !== []) {
            return [
                'status' => 'error',
                'message' => trans('froxlor-packages::generic.package_has_dependants', [
                    'package' => $package,
                    'dependants' => implode(', ', array_keys($dependants)),
                ]),
            ];
        }

        $process = $this->process('remove', '--no-ansi', '--no-interaction', '--no-progress', $package);

        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => $process->getErrorOutput()
            ];
        }

        Cache::forget('packages');

        return [
            'status' => 'success',
            'message' => 'Package ' . $package . ' has been removed successfully.'
        ];
    }

    /**
     * The list of repositories as configured directly in composer.json — composer.json is the
     * single source of truth, there is no separate enabled/disabled state to track: a repository
     * either is or isn't present in the file.
     *
     * Each entry carries a URL-safe `id` (the name with '/' replaced by ':', mirroring how
     * packages are addressed in routes), since repository names created from package names
     * (e.g. by loadPackageRepository()) can contain slashes.
     *
     * @return array<int, array<string, mixed>> repository entries plus `id`/`protected`/`verified`
     */
    public function repositories(): array
    {
        $repositories = $this->composerJson()['repositories'] ?? [];

        return array_map(fn (array $repository) => [
            ...$repository,
            'id' => isset($repository['name']) ? str_replace('/', ':', $repository['name']) : null,
            'protected' => $this->isProtectedRepository($repository['url'] ?? null),
            'verified' => $this->isVerifiedRepository($repository['url'] ?? null),
        ], $repositories);
    }

    /**
     * Find a configured repository by its name, or null when none matches.
     *
     * @return array<string, mixed>|null
     */
    public function findRepository(string $name): ?array
    {
        foreach ($this->repositories() as $repository) {
            if (($repository['name'] ?? null) === $name) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Add (or overwrite) a repository entry in composer.json.
     *
     * @throws Exception when composer rejects the configuration change
     */
    public function addRepository(string $name, string $type, string $url, ?array $options = null): void
    {
        $process = $this->process('config', 'repositories.' . $name, json_encode([
            'type' => $type,
            'url' => $url,
            ...$options ? ['options' => $options] : [],
        ]));

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }
    }

    /**
     * Add a symlinked path repository, as used for locally mounted development packages.
     *
     * @throws Exception when composer rejects the configuration change
     */
    public function addLocalRepository(string $name, string $directory): void
    {
        $this->addRepository($name, 'path', $directory, [
            'reference' => 'config',
            'symlink' => true,
        ]);
    }

    /**
     * Remove a repository entry from composer.json.
     *
     * @throws Exception when composer rejects the configuration change
     */
    public function removeRepository(string $name): void
    {
        $process = $this->process('config', '--unset', 'repositories.' . $name);

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }
    }

    /**
     * Persist repository credentials into composer's own auth.json (stored alongside composer.json,
     * outside of version control) rather than a separate encrypted store.
     *
     * @param string $type 'http-basic' (username/password) or 'bearer' (token)
     * @param array<string, string> $credentials keys matching the auth type
     * @throws Exception when composer rejects the change
     * @throws InvalidArgumentException when the auth type is unsupported
     */
    public function setRepositoryAuth(string $host, string $type, array $credentials): void
    {
        $args = match ($type) {
            'http-basic' => [$credentials['username'], $credentials['password']],
            'bearer' => [$credentials['token']],
            default => throw new InvalidArgumentException("Unsupported auth type [$type]."),
        };

        $process = $this->process('config', '--auth', $type . '.' . $host, ...$args);

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }
    }

    /**
     * Remove stored repository credentials from composer's auth.json.
     *
     * @throws Exception when composer rejects the change
     */
    public function removeRepositoryAuth(string $host, string $type = 'http-basic'): void
    {
        $process = $this->process('config', '--auth', '--unset', $type . '.' . $host);

        if (!$process->isSuccessful()) {
            throw new Exception($process->getErrorOutput());
        }
    }

    /**
     * The application's decoded composer.json.
     *
     * @return array<string, mixed>
     */
    private function composerJson(): array
    {
        return json_decode(file_get_contents(base_path('composer.json')), true) ?? [];
    }

    /**
     * Whether the URL is the official froxlor repository, which must never be removed.
     */
    private function isProtectedRepository(?string $url): bool
    {
        return $url === self::PROTECTED_REPOSITORY_URL;
    }

    /**
     * Whether the URL is one of the repositories operated by the froxlor project itself.
     */
    private function isVerifiedRepository(?string $url): bool
    {
        return in_array($url, self::VERIFIED_REPOSITORY_URLS, true);
    }

    /**
     * All currently installed froxlor packages from composer.lock, enriched with their
     * runtime state (safe mode, enabled/disabled, pending completion, dependants).
     *
     * @return array<int, array<string, mixed>>
     */
    public function packages(): array
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        $disabled = app(SafeModeRegistry::class)->disabled();
        $found = [];

        foreach ($packages as $package) {
            if (isset($package['extra']['froxlor']['type']) && $package['extra']['froxlor']['type'] === 'package') {
                $safeModeEntry = $disabled[$package['name']] ?? null;
                $provider = $this->findProvider($package['name']);
                $pending = $provider?->pendingCompletion();
                $dependants = $this->findDependant($package['name']);

                $found[] = [
                    'id' => str_replace('/', ':', $package['name']),
                    'name' => $package['name'],
                    'installed' => true,
                    'version' => $package['version'],
                    'description' => $package['description'] ?? null,
                    'homepage' => $package['homepage'] ?? null,
                    'authors' => $package['authors'] ?? null,
                    'license' => $package['license'] ?? null,
                    'dependant' => $dependants,
                    // Table columns may collapse `dependant` into a display string via
                    // formatValue() before row-level visible()/disabled() closures run
                    // against the same row, so expose the raw fact separately here.
                    'has_dependants' => $dependants !== [],
                    'depends' => $package['require'] ?? null,
                    'disabled' => $safeModeEntry !== null,
                    'disabled_reason' => $safeModeEntry['reason'] ?? null,
                    'disabled_auto' => $safeModeEntry['auto'] ?? null,
                    'disabled_at' => $safeModeEntry['disabled_at'] ?? null,
                    'toggleable' => $this->isToggleable($package['name']),
                    'enabled' => $provider?->isEnabled() ?? true,
                    'pending_reason' => $pending['reason'] ?? null,
                    'pending_route' => $pending['route'] ?? null,
                ];
            }
        }

        return $found;
    }

    /**
     * Resolve which installed froxlor package owns a given file path, used by safe mode to
     * figure out which package to disable when its code throws during boot.
     */
    public function findPackageForPath(string $path): ?string
    {
        return ComposerPackage::forPath($path);
    }

    /**
     * froxlor/framework bundles the mandatory core/packages/ui providers under one composer
     * package — it must never be individually disabled, deliberately or via safe mode.
     */
    public function isToggleable(string $name): bool
    {
        return $name !== 'froxlor/framework';
    }

    /**
     * Resolve the currently loaded service provider instance for an installed froxlor package.
     */
    public function findProvider(string $packageName): ?PackageServiceProvider
    {
        foreach (app()->getProviders(PackageServiceProvider::class) as $provider) {
            if ($provider->packageName() === $packageName) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Packages whose update hook couldn't get an answer it needed and is waiting on the admin
     * to provide one, used by EnsurePackageUpdatesAreComplete to enforce completion.
     *
     * @return array<string, array{reason: string, route: string}>
     */
    public function pendingCompletions(): array
    {
        $pending = [];

        foreach (app()->getProviders(PackageServiceProvider::class) as $provider) {
            $name = $provider->packageName();

            if (!$this->isToggleable($name) || !$provider->isEnabled()) {
                continue;
            }

            if ($completion = $provider->pendingCompletion()) {
                $pending[$name] = $completion;
            }
        }

        return $pending;
    }

    /**
     * Enable a package via its service provider.
     *
     * @throws Exception when the package is not toggleable or has no provider
     */
    public function enablePackage(string $packageName): void
    {
        $provider = $this->requireToggleableProvider($packageName);

        $provider->enable();
    }

    /**
     * Disable a package via its service provider.
     *
     * @throws Exception when the package is not toggleable or has no provider
     */
    public function disablePackage(string $packageName): void
    {
        $provider = $this->requireToggleableProvider($packageName);

        $provider->disable();
    }

    /**
     * Resolve the provider of a package that may be enabled/disabled.
     *
     * @throws Exception when the package is not toggleable or has no provider
     */
    private function requireToggleableProvider(string $packageName): PackageServiceProvider
    {
        if (!$this->isToggleable($packageName)) {
            throw new Exception("The package {$packageName} cannot be enabled or disabled.");
        }

        $provider = $this->findProvider($packageName);

        if (!$provider) {
            throw new Exception("No service provider found for package {$packageName}.");
        }

        return $provider;
    }

    /**
     * @return array<string, string> name of package that depends on $package => version constraint
     */
    public function findDependant(string $package): array
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        $froxlorPackages = array_filter(
            $packages,
            fn (array $p) => ($p['extra']['froxlor']['type'] ?? null) === 'package'
        );

        // A package can satisfy another package's `require` under a different name via
        // `replace` (e.g. froxlor/framework replaces froxlor/core, froxlor/packages, froxlor/ui),
        // so any dependant requiring one of those replaced names counts as depending on $package too.
        $providedNames = [$package];

        foreach ($froxlorPackages as $p) {
            if ($p['name'] === $package) {
                $providedNames = array_merge($providedNames, array_keys($p['replace'] ?? []));
            }
        }

        $found = [];

        foreach ($froxlorPackages as $p) {
            if ($p['name'] === $package || !is_array($p['require'] ?? null)) {
                continue;
            }

            foreach ($providedNames as $providedName) {
                if (isset($p['require'][$providedName])) {
                    $found[$p['name']] = $p['require'][$providedName];
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * All packages installable from the configured discovery repository, each entry being the
     * latest version's composer metadata plus an `id` and `installed` flag.
     *
     * Results are cached for five minutes; failures are logged and not cached (the closure
     * returns null, which Cache::remember treats as a miss), so the next call retries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availablePackages(): array
    {
        return Cache::remember('packages', 300, fn () => $this->fetchAvailablePackages()) ?? [];
    }

    /**
     * Fetch and flatten the discovery repository's package index (packages.json plus its
     * includes), or null when the repository is unreachable or responds with garbage.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchAvailablePackages(): ?array
    {
        $discoveryUrl = config('packages.discovery');

        $client = new Client([
            'base_uri' => $discoveryUrl,
            'timeout' => 10.0,
            'verify' => true,
        ]);

        $options = [
            'headers' => [
                'User-Agent' => FroxlorVersion::userAgent(),
            ],
        ];

        if (MarketplaceCredentials::configured()) {
            $options['auth'] = [MarketplaceCredentials::username(), MarketplaceCredentials::token()];
        }

        try {
            $response = $client->get('/packages.json', $options);

            $root = json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::warning('Could not reach the package discovery repository.', [
                'url' => $discoveryUrl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!is_array($root)) {
            Log::warning('The package discovery repository returned an unexpected response.', [
                'url' => $discoveryUrl,
            ]);

            return null;
        }

        $packages = [];

        foreach ($root['includes'] ?? [] as $include => $meta) {
            try {
                $response = $client->get($include, $options);
                $json = json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::warning('Could not fetch a package index of the discovery repository, skipping it.', [
                    'url' => $discoveryUrl,
                    'include' => $include,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            if (!is_array($json) || !isset($json['packages'])) {
                Log::warning('A package index of the discovery repository returned an unexpected response, skipping it.', [
                    'url' => $discoveryUrl,
                    'include' => $include,
                ]);

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
    }

    /**
     * Installed froxlor packages for which the discovery repository offers a newer version,
     * each entry enriched with `latest_version` and `latest_description`.
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Whether at least one installed package has an update available.
     */
    public function hasAvailableUpdates(): bool
    {
        return $this->availableUpdates() !== [];
    }

    /**
     * Compare two version strings, ignoring a leading 'v' prefix.
     */
    private function isNewerVersion(string $availableVersion, string $installedVersion): bool
    {
        return version_compare(
            ltrim($availableVersion, 'v'),
            ltrim($installedVersion, 'v'),
            '>'
        );
    }

    /**
     * Pin an unconstrained package requirement to the latest version known to the discovery
     * repository; requirements that already carry a constraint are passed through unchanged.
     */
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

    /**
     * Whether the requirement string already contains a version constraint ('vendor/name:^1.0').
     */
    private function hasVersionConstraint(string $package): bool
    {
        return str_contains($package, ':');
    }

    /**
     * Reset the repository configuration to only the official froxlor repository, removing
     * every other configured repository.
     *
     * @return array{status: string, message: string}
     */
    public function changeToDefaultRepository(): array
    {
        try {
            foreach ($this->repositories() as $repository) {
                if (($repository['name'] ?? null) !== 'froxlor') {
                    $this->removeRepository($repository['name']);
                }
            }

            if (!$this->findRepository('froxlor')) {
                $this->addRepository('froxlor', 'composer', self::PROTECTED_REPOSITORY_URL);
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'message' => trans('froxlor-packages::generic.repositories_updated_successfully'),
        ];
    }

    /**
     * Register the locally mounted development packages from the dev config (FROXLOR_DEV_PACKAGES)
     * as path repositories and require them at dev-main.
     *
     * @return array{status: string, message: string}
     */
    public function loadPackageRepository(): array
    {
        $packages = explode(',', config('dev.packages'));

        try {
            // add repositories
            foreach ($packages as $package) {
                $meta = explode('::', $package);
                $name = $meta[0];
                $directory = $meta[1] ?? null;

                if (!is_dir($directory) || !is_writable($directory)) {
                    continue;
                }

                $this->addLocalRepository($name, $directory);
            }

            // enable packages
            foreach ($packages as $package) {
                $meta = explode(':', $package);
                $name = $meta[0];

                $response = $this->requirePackage($name . ':dev-main');

                if ($response['status'] !== 'success') {
                    return $response;
                }
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'message' => trans('froxlor-packages::generic.repositories_updated_successfully'),
        ];
    }
}
