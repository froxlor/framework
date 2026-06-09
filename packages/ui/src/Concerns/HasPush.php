<?php

namespace Froxlor\UI\Concerns;

use Froxlor\Core\Support\Api;
use Froxlor\UI\Exceptions\ApiException;
use Froxlor\UI\Support\UrlResolver;

/**
 * @property ?object $push
 */
trait HasPush
{
    public ?object $push = null;

    public mixed $intended = null;

    public function push(string $url, string $method = 'POST'): static
    {
        $this->push = (object)[
            'url' => $url,
            'method' => strtoupper($method),
        ];

        return $this;
    }

    /**
     * @throws ApiException
     */
    public function submit(array $data): ?string
    {
        if (!$this->push) {
            throw new ApiException('The form cannot be submitted because no push method has been configured.');
        }

        $response = Api::request($this->push->method, $this->push->url, $data);
        $intended = $this->intended ?? '/';
        $item = $response->first();

        if (is_array($item) && function_exists('session')) {
            session()->put('_ui.response_item', $item);
        }

        if (is_object($intended) && isset($intended->route)) {
            return UrlResolver::resolve([
                'route' => $intended->route,
                'attributes' => (array)($intended->attributes ?? []),
            ], is_array($item) ? $item : []);
        }

        if (is_array($intended) && isset($intended['route'])) {
            return UrlResolver::resolve($intended, is_array($item) ? $item : []);
        }

        return is_string($intended) ? $intended : '/';
    }
}
