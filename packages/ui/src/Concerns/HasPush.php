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

    public ?object $notification = null;

    public function push(string $url, string $method = 'POST'): static
    {
        $this->push = (object)[
            'url' => $url,
            'method' => strtoupper($method),
        ];

        return $this;
    }

    /**
     * Show a toast on successful submit instead of redirecting, unless an intendedRoute
     * is also set explicitly.
     */
    public function notifyOnSubmit(string $title, ?string $description = null, string $variant = 'success'): static
    {
        $this->notification = (object)[
            'title' => $title,
            'description' => $description,
            'variant' => $variant,
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
        $item = $response->first();

        if (is_array($item) && function_exists('session')) {
            session()->put('_ui.response_item', $item);
        }

        if ($this->intended === null && $this->notification !== null) {
            return null;
        }

        $intended = $this->intended ?? '/';

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
