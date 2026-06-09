<?php

namespace Froxlor\Core\Support;

use Exception;
use Froxlor\Core\Services\Api\Request;
use Froxlor\UI\Exceptions\ApiException;
use Illuminate\Http\RedirectResponse;

class Api
{
    /**
     * Makes an internal API request and returns the response wrapped in a Request instance.
     *
     * @param string $method HTTP method (e.g., 'GET', 'POST').
     * @param string $uri The URI to request.
     * @param array $body Optional body to include in the request.
     * @param array $parameters Optional parameters to include in the request.
     * @param bool $pagination Whether to include pagination parameters.
     * @return Request|RedirectResponse The API request instance containing the response data.
     * @throws ApiException
     */
    public static function request(string $method, string $uri, array $body = [], array $parameters = [], bool $pagination = false): Request|RedirectResponse
    {
        return new Request()->request($method, $uri, $body, $parameters, $pagination);
    }
}
