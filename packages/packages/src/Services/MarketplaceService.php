<?php

namespace Froxlor\Packages\Services;

use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Packages\Support\Markdown;
use Froxlor\Packages\Support\MarketplaceCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Builds the marketplace card catalog by cross-referencing the packages actually available
 * from the configured discovery repository (PackageService::availablePackages(), the source of
 * truth for what's installable) against packages.froxlor.org's marketplace.json, which supplies
 * presentation metadata (title, header image, price, long-form Markdown description, ...).
 *
 * A package present in the repository but missing from the marketplace catalog still gets a
 * card — built from its own composer metadata — since it's still installable.
 */
class MarketplaceService
{
    protected static array $ignoredPackages = [
        'froxlor/framework',
    ];

    public function __construct(private readonly PackageService $packageService)
    {
    }

    public function catalog(): array
    {
        $marketplace = $this->marketplacePackagesByName();

        return array_filter(
            array_map(
                fn(array $package) => in_array($package['name'], self::$ignoredPackages) ? null
                    : $this->buildCard($package, $marketplace[$package['name']] ?? null),
                $this->packageService->availablePackages()
            )
        );
    }

    /**
     * @return array<string, array>
     */
    private function marketplacePackagesByName(): array
    {
        $packages = [];

        foreach ($this->marketplacePackages() as $package) {
            if (!empty($package['name'])) {
                $packages[$package['name']] = $package;
            }
        }

        return $packages;
    }

    private function marketplacePackages(): array
    {
        return Cache::remember('packages.marketplace', 3600, function () {
            $remote = $this->fetchRemoteMarketplace();

            return $remote ?? $this->fetchLocalMarketplace();
        });
    }

    private function fetchRemoteMarketplace(): ?array
    {
        $url = config('packages.marketplace');

        if (!$url) {
            return null;
        }

        try {
            $client = new Client(['timeout' => 10.0, 'verify' => true]);

            $options = [
                'headers' => [
                    'User-Agent' => FroxlorVersion::userAgent(),
                ],
            ];

            if (MarketplaceCredentials::configured()) {
                $options['auth'] = [MarketplaceCredentials::username(), MarketplaceCredentials::token()];
            }

            $response = $client->get($url, $options);
            $json = json_decode($response->getBody()->getContents(), true);

            return is_array($json['packages'] ?? null) ? $json['packages'] : null;
        } catch (GuzzleException $e) {
            Log::warning('Could not reach the packages.froxlor.org marketplace, falling back to the local marketplace.json.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function fetchLocalMarketplace(): array
    {
        $path = __DIR__ . '/../../marketplace.json';

        if (!is_file($path)) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true);

        return is_array($json['packages'] ?? null) ? $json['packages'] : [];
    }

    private function buildCard(array $package, ?array $marketplace): array
    {
        $content = $marketplace['content'] ?? $package['description'] ?? null;
        $price = (float)($marketplace['price'] ?? 0);

        return [
            'id' => $package['id'] ?? str_replace('/', ':', $package['name']),
            'name' => $package['name'],
            'installed' => (bool)($package['installed'] ?? false),
            'title' => $marketplace['title'] ?? $package['name'],
            'description' => $marketplace['description'] ?? $package['description'] ?? null,
            'content_html' => Markdown::toHtml($content),
            'image' => $marketplace['image'] ?? null,
            'price' => $price,
            'currency' => $marketplace['currency'] ?? 'EUR',
            'website' => $marketplace['website'] ?? $package['homepage'] ?? null,
            'version' => $package['version'] ?? null,
            'from_marketplace' => $marketplace !== null,
            'requires_purchase' => $price > 0,
            'requires_credentials' => $price > 0 && !MarketplaceCredentials::configured(),
        ];
    }
}
