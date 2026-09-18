<?php

namespace Froxlor\UI\Concerns;

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use RuntimeException;

trait HasAssets
{
    /** @var array<string, list<string>> */
    protected static array $assetBundles = [];

    /**
     * Register the Blade directive, this is loaded once, so we need to wrap render assets.
     *
     * @param string $publicPath The public path where the assets are located, e.g. 'css', 'js'
     * @param array $assets An array of asset filenames, e.g. ['styles.css', 'app.js']
     */
    public static function assetsDirective(string $publicPath, array $assets): void
    {
        self::assertAssetPath($publicPath);
        foreach ($assets as $asset) {
            if (! is_string($asset) || $asset === '' || str_starts_with($asset, '/') || str_contains($asset, '..')) {
                throw new InvalidArgumentException('UI assets must be relative paths without traversal segments.');
            }
        }

        self::$assetBundles[$publicPath] = array_values(array_unique([
            ...self::$assetBundles[$publicPath] ?? [],
            ...$assets,
        ]));

        Blade::directive('froxlorHead', static function () {
            return '<?php echo \\Froxlor\\UI\\Support\\UI::renderRegisteredAssets(); ?>';
        });
    }

    /** Render every asset bundle registered by the loaded packages. */
    public static function renderRegisteredAssets(): string
    {
        $html = [];
        foreach (self::$assetBundles as $publicPath => $assets) {
            $html[] = self::renderBundle($publicPath, $assets);
        }
        $html[] = self::getCssVariables();

        return implode("\n", array_filter($html));
    }

    /**
     * Generate HTML tags for assets (CSS and JS) with cache-busting query parameters based on file hashes.
     *
     * @param string $publicPath The public path where the assets are located, e.g. 'css', 'js'
     * @param array $assets An array of asset filenames, e.g. ['styles.css', 'app.js']
     * @throws \Exception
     */
    public static function renderAssets(string $publicPath, array $assets): string
    {
        self::assertAssetPath($publicPath);

        return self::renderBundle($publicPath, $assets)."\n".self::getCssVariables();
    }

    /** @param list<string> $assets */
    private static function renderBundle(string $publicPath, array $assets): string
    {
        $html = [];

        foreach ($assets as $asset) {
            if (! is_string($asset) || $asset === '' || str_starts_with($asset, '/') || str_contains($asset, '..')) {
                throw new InvalidArgumentException('UI assets must be relative paths without traversal segments.');
            }
            $path = public_path($publicPath . '/' . $asset);

            if (! is_file($path)) {
                throw new RuntimeException(sprintf(
                    'UI asset is missing: %s. Publish or link the package assets before rendering the application.',
                    $path,
                ));
            }

            $hash = md5_file($path);
            if ($hash === false) {
                throw new RuntimeException('Unable to hash UI asset: '.$path);
            }
            $url = asset($publicPath . '/' . $asset) . '?v=' . $hash;

            if (str_ends_with($asset, '.css')) {
                $html[] = "<link rel=\"stylesheet\" href=\"{$url}\" data-navigate-track>";
            } elseif (str_ends_with($asset, '.js')) {
                $type = basename($asset) === 'app.js' ? ' type="module"' : '';
                $html[] = "<script{$type} src=\"{$url}\" data-navigate-track></script>";
            }
        }

        return implode("\n", $html);
    }

    private static function assertAssetPath(string $path): void
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..')) {
            throw new InvalidArgumentException('UI asset bundle paths must be relative and traversal-safe.');
        }
    }

    /**
     * Get the style tag for theme adjustments, built from the individual
     * `appearance.colors.*` settings rows (one setting per CSS variable).
     */
    private static function getCssVariables(): string
    {
        $colors = SettingModel::query()
            ->where('category', 'appearance')
            ->where('key', 'like', 'colors.%')
            ->get();

        $theme = self::cssVariables($colors, 'colors.base.');
        $themeDark = self::cssVariables($colors, 'colors.dark.');

        $variant = in_array(Setting::get('appearance.theme'), ['light', 'dark'])
            ? '@custom-variant dark (&:where(.dark, .dark *));'
            : '';

        return "<style type=\"text/tailwindcss\">$variant @theme { $theme } @layer theme { :root, :host { @variant dark { $themeDark } } }</style>";
    }

    /**
     * Map settings whose key starts with the given prefix to CSS variable
     * declarations, e.g. `colors.base.color-primary` => `--color-primary: ...;`.
     */
    private static function cssVariables(Collection $colors, string $prefix): string
    {
        return $colors
            ->filter(fn(SettingModel $setting) => str_starts_with($setting->key, $prefix))
            ->map(function (SettingModel $setting) use ($prefix) {
                $value = $setting->value ?? $setting->default_value;

                return $value ? '--' . substr($setting->key, strlen($prefix)) . ": {$value};" : null;
            })
            ->filter()
            ->implode(' ');
    }
}
