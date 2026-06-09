<?php

namespace Froxlor\UI\Concerns;

use Froxlor\Core\Support\Setting;
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
     * Get the style tag for theme adjustments.
     */
    private static function getCssVariables(): string
    {
        $theme = collect(Setting::get('ui.colors.base', []))
            ->map(fn($value, $key) => "--{$key}: {$value};")
            ->implode(' ');

        $themeDark = collect(Setting::get('ui.colors.dark', []))
            ->map(fn($value, $key) => "--{$key}: {$value};")
            ->implode(' ');

        $variant = in_array(Setting::get('ui.theme'), ['light', 'dark'])
            ? '@custom-variant dark (&:where(.dark, .dark *));'
            : '';

        return "<style type=\"text/tailwindcss\">$variant @theme { $theme } @layer theme { :root, :host { @variant dark { $themeDark } } }</style>";
    }
}
