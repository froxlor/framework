<?php

namespace Froxlor\Packages\Support;

use Froxlor\Packages\Services\PackageService;
use Illuminate\Support\Carbon;

/**
 * Tracks froxlor packages that have been soft-disabled because they failed a composer
 * operation or crashed the application at boot. Deliberately file-backed (not a DB table)
 * so it keeps working even when the database or a migration is the thing that's broken.
 *
 * Also deliberately avoids facades/container-resolved services (File, now(), ...): this class
 * is consulted by SafeModePackageManifest while Laravel is still assembling the list of service
 * providers to register, before almost anything — including the 'files' binding — exists yet.
 */
class SafeModeRegistry
{
    public function __construct(private readonly PackageService $packageService)
    {
    }

    public function disabled(): array
    {
        if (!file_exists($this->path())) {
            return [];
        }

        $data = json_decode(file_get_contents($this->path()), true);

        return $data['disabled'] ?? [];
    }

    public function isDisabled(string $package): bool
    {
        return array_key_exists($package, $this->disabled());
    }

    public function disable(string $package, string $reason, bool $auto = false): void
    {
        if (!$this->isDisableable($package)) {
            return;
        }

        $disabled = $this->disabled();

        $disabled[$package] = [
            'reason' => $reason,
            'auto' => $auto,
            'disabled_at' => Carbon::now()->toIso8601String(),
        ];

        $this->write($disabled);
    }

    public function enable(string $package): void
    {
        $disabled = $this->disabled();

        if (!array_key_exists($package, $disabled)) {
            return;
        }

        unset($disabled[$package]);

        $this->write($disabled);
    }

    private function isDisableable(string $package): bool
    {
        if (!$this->packageService->isToggleable($package)) {
            return false;
        }

        foreach ($this->packageService->packages() as $installed) {
            if ($installed['name'] === $package) {
                return true;
            }
        }

        return false;
    }

    private function path(): string
    {
        return storage_path('framework/froxlor-safe-mode.json');
    }

    private function write(array $disabled): void
    {
        $directory = dirname($this->path());

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->path(), json_encode(['disabled' => $disabled], JSON_PRETTY_PRINT));

        $this->purgeManifestCache();
    }

    private function purgeManifestCache(): void
    {
        foreach ([app()->getCachedPackagesPath(), app()->getCachedServicesPath()] as $cachePath) {
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
        }
    }
}
