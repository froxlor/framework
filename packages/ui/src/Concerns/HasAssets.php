<?php

namespace Froxlor\UI\Concerns;

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;

trait HasAssets
{
    /**
     * Register the Blade directive, this is loaded once, so we need to wrap render assets.
     *
     * @param string $publicPath The public path where the assets are located, e.g. 'css', 'js'
     * @param array $assets An array of asset filenames, e.g. ['styles.css', 'app.js']
     */
    public static function assetsDirective(string $publicPath, array $assets): void
    {
        Blade::directive('froxlorHead', function () use ($publicPath, $assets) {
            return "<?php echo \\Froxlor\\UI\\Support\\UI::renderAssets('$publicPath', " . var_export($assets, true) . "); ?>";
        });
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
        $html = [];

        foreach ($assets as $asset) {
            $path = public_path($publicPath . '/' . $asset);

            if (!file_exists($path)) {
                continue;
            }

            $hash = md5_file($path);
            $url = asset($publicPath . '/' . $asset) . '?v=' . $hash;

            if (str_ends_with($asset, '.css')) {
                $html[] = "<link rel=\"stylesheet\" href=\"{$url}\" data-navigate-track>";
            } elseif (str_ends_with($asset, '.js')) {
                $type = basename($asset) === 'app.js' ? ' type="module"' : '';
                $html[] = "<script{$type} src=\"{$url}\" data-navigate-track></script>";
            }
        }

        $html[] = self::getCssVariables();

        return implode("\n", $html);
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
