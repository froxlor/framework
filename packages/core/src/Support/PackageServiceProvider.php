<?php

namespace Froxlor\Core\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Base class for every froxlor package's own service provider. Beyond the auto-discovery of
 * console commands, it defines the package lifecycle contract: a package can be deliberately
 * enabled/disabled (state kept in a Setting, independent of the Packages module's file-backed
 * "safe mode" crash recovery), and can hook into installing/enabling/disabling/updating.
 *
 * Because Settings are database-backed, they cannot be consulted during Laravel's earliest
 * bootstrap phase (before the database service provider itself has registered) — so a disabled
 * package's provider still loads normally. Concrete providers are expected to self-gate: register
 * migrations/translations unconditionally in boot(), then bail out of registering routes/views/UI
 * pushes/policies with `if (!$this->isEnabled()) { return; }`.
 */
abstract class PackageServiceProvider extends ServiceProvider
{
    /**
     * The composer package name (e.g. "froxlor/example") that this provider belongs to.
     */
    public function packageName(): string
    {
        return ComposerPackage::forClass(static::class)
            ?? throw new RuntimeException('Unable to resolve the composer package for ' . static::class);
    }

    public function isEnabled(): bool
    {
        return (bool) Setting::get($this->enabledSettingPath(), true);
    }

    /**
     * Called before a package's migrations run for the first time. Fired by the Packages
     * module's PackagesSync command in a fresh subprocess, once the newly installed package's
     * own classes are actually autoloadable, but *before* `artisan migrate` runs — this is the
     * package's one chance to ensure any settings its migrations depend on already exist before
     * that schema/data change is applied. Call requireCompletion() here to block migrations (for
     * every package in this sync run, see PackagesSync) until an admin has supplied one.
     */
    public function installing(): void
    {
        //
    }

    /**
     * Called after a package's migrations have run for the first time.
     */
    public function installed(): void
    {
        //
    }

    /**
     * Called before/after an admin deliberately enables this package.
     */
    public function enabling(): void
    {
        //
    }

    public function enabled(): void
    {
        //
    }

    /**
     * Called before/after an admin deliberately disables this package.
     */
    public function disabling(): void
    {
        //
    }

    public function disabled(): void
    {
        //
    }

    /**
     * Called before this package is updated to its new version's migrations being run — i.e.
     * the new code is already autoloadable (composer has already required/updated it) but the
     * database still reflects the old version. Use this, not updated(), to gate a migration that
     * needs a setting to already exist (see requireCompletion()) — by the time updated() runs
     * the migration has already executed, which is too late to supply a missing prerequisite.
     */
    public function updating(): void
    {
        //
    }

    /**
     * Called after this package's migrations for the new version have run.
     */
    public function updated(): void
    {
        //
    }

    /**
     * Whether this process can genuinely block for interactive input right now (a real terminal
     * is attached), as opposed to running inside a subprocess with no TTY (the web UI's update
     * flow) or a non-interactive CLI invocation (cron, CI, etc). Hooks like updating() use this to
     * decide between prompting directly and calling requireCompletion() instead.
     */
    public function isConsoleInteractive(): bool
    {
        return $this->app->runningInConsole() && defined('STDIN') && stream_isatty(STDIN);
    }

    /**
     * Flags this package as needing admin input before it's considered fully up to date. Until
     * completeCompletion() is called, the Packages module's EnsurePackageUpdatesAreComplete
     * middleware redirects every request to $route.
     *
     * $stage records which lifecycle hook is waiting — 'installing' (called from installing(),
     * before this package's first migrations) or 'updating' (called from updating(), before its
     * next version's migrations) — so completeCompletion() knows whether to fire installed() or
     * updated() once the admin has supplied what was missing. When called from installing() or
     * updating(), PackagesSync also uses the presence of a pending completion to hold back
     * `artisan migrate` for this sync run entirely, so the migration never runs without it.
     */
    public function requireCompletion(string $reason, string $route, string $stage = 'updating'): void
    {
        Setting::set($this->pendingCompletionSettingPath(), [
            'reason' => $reason,
            'route' => $route,
            'stage' => $stage,
        ]);
    }

    /**
     * Clears a pending completion and, if one was actually pending, finishes the lifecycle it
     * interrupted: runs any migrations that were held back waiting for it, then fires installed()
     * or updated() (per the stage recorded by requireCompletion()) exactly as PackagesSync would
     * have if the admin's input had been available immediately.
     */
    public function completeCompletion(): void
    {
        $pending = $this->pendingCompletion();

        // The settings table's value column is NOT NULL, so an empty array is the "cleared"
        // sentinel rather than null.
        Setting::set($this->pendingCompletionSettingPath(), []);

        if ($pending === null) {
            return;
        }

        Artisan::call('migrate', ['--force' => true]);

        match ($pending['stage'] ?? 'updating') {
            'installing' => $this->installed(),
            default => $this->updated(),
        };
    }

    public function pendingCompletion(): ?array
    {
        $pending = Setting::get($this->pendingCompletionSettingPath());

        return empty($pending) ? null : $pending;
    }

    public function hasPendingCompletion(): bool
    {
        return $this->pendingCompletion() !== null;
    }

    private function pendingCompletionSettingPath(): string
    {
        return 'packages.' . $this->packageName() . '.pending';
    }

    public function enable(): void
    {
        if ($this->isEnabled()) {
            return;
        }

        $this->enabling();
        Setting::set($this->enabledSettingPath(), true, 'boolean', true);
        $this->enabled();

        Audit::info(sprintf('Package %s has been enabled.', $this->packageName()));
    }

    public function disable(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->disabling();
        Setting::set($this->enabledSettingPath(), false, 'boolean', true);
        $this->disabled();

        Audit::info(sprintf('Package %s has been disabled.', $this->packageName()));
    }

    private function enabledSettingPath(): string
    {
        return 'packages.' . $this->packageName() . '.enabled';
    }

    public function loadCommandsFrom(string $path): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (! is_dir($path)) {
            return;
        }

        $commands = [];

        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->classFromFile($file->getPathname());

            if (! $class) {
                continue;
            }

            if (is_subclass_of($class, Command::class)) {
                $commands[] = $class;
            }
        }

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    private function classFromFile(string $path): ?string
    {
        $source = @file_get_contents($path);

        if ($source === false) {
            return null;
        }

        $tokens = token_get_all($source);
        $namespace = '';
        $class = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                $i++;

                while ($i < $count) {
                    $current = $tokens[$i];

                    if (is_array($current) && in_array($current[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $current[1];
                        $i++;
                        continue;
                    }

                    if ($current === ';' || $current === '{') {
                        break;
                    }

                    $i++;
                }

                continue;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $previous = $this->previousNonWhitespaceToken($tokens, $i);

                if (is_array($previous) && $previous[0] === T_NEW) {
                    continue;
                }

                $i++;

                while ($i < $count) {
                    $current = $tokens[$i];

                    if (is_array($current) && $current[0] === T_STRING) {
                        $class = $current[1];
                        break 2;
                    }

                    $i++;
                }
            }
        }

        if (! $class) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    private function previousNonWhitespaceToken(array $tokens, int $index): mixed
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }

            return $token;
        }

        return null;
    }
}
