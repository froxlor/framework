<?php

namespace Froxlor\Core\Services\Api;

use Froxlor\UI\Exceptions\ApiException;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Facade;
use JsonException;
use ReflectionObject;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Request
{
    public ?array $data = null;
    public ?Response $response = null;

    /**
     * @throws ApiException
     */
    public function request(
        string $method,
        string $uri,
        array $body = [],
        array $parameters = [],
        bool $pagination = false
    ): self {
        $request = $this->buildRequest($method, $uri, $body, $this->resolveParameters($method, $parameters, $pagination));

        $this->response = $this->dispatch($request);
        $this->data = json_decode($this->response->getContent(), true);

        $this->assertSuccessful();

        return $this;
    }

    public function first(): mixed
    {
        $data = $this->data();

        if (!is_array($data)) {
            return null;
        }

        if (array_is_list($data)) {
            return $data[0] ?? null;
        }

        return $data;
    }

    public function data(): array
    {
        return $this->data['data'] ?? [];
    }

    public function toArray(): array
    {
        return $this->data ?? [];
    }

    private function resolveParameters(string $method, array $parameters, bool $pagination): array
    {
        if (strtolower($method) === 'get' && !$pagination) {
            return array_merge(['limit' => 0], $parameters);
        }

        return $parameters;
    }

    /**
     * @throws ApiException
     */
    private function buildRequest(string $method, string $uri, array $body, array $parameters): IlluminateRequest
    {
        try {
            $request = IlluminateRequest::create(
                uri: $uri,
                method: $method,
                parameters: $parameters,
                content: $body !== [] ? json_encode($body, JSON_THROW_ON_ERROR) : null
            );
        } catch (JsonException $e) {
            throw ApiException::fromThrowable('Failed to encode request body.', $e);
        }

        if ($body !== []) {
            $request->request->add($body);
            $request->setJson(new InputBag($body));
        }

        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    /**
     * @throws ApiException
     */
    private function dispatch(IlluminateRequest $request): Response
    {
        try {
            $app = app();
            $kernel = $app->make(HttpKernelContract::class);
        } catch (Throwable $e) {
            throw ApiException::fromThrowable('Failed to resolve HTTP kernel.', $e);
        }

        [$originalRequest, $originalRoute, $originalRouteResolver] = $this->captureAppState($app);

        try {
            return $kernel->handle($request);
        } catch (Throwable $e) {
            throw ApiException::fromThrowable('Failed to dispatch internal request.', $e);
        } finally {
            $this->restoreAppState($app, $originalRequest, $originalRoute, $originalRouteResolver);
        }
    }

    private function captureAppState($app): array
    {
        $originalRequest = $app->bound('request') ? $app['request'] : null;
        $router = $app->bound('router') ? $app['router'] : null;
        $originalRoute = $router?->current();
        $originalRouteResolver = $originalRequest?->getRouteResolver();

        return [$originalRequest, $originalRoute, $originalRouteResolver];
    }

    private function restoreAppState($app, $originalRequest, $originalRoute, $originalRouteResolver): void
    {
        if ($originalRequest !== null) {
            $app->instance('request', $originalRequest);
        } else {
            $app->forgetInstance('request');
        }

        Facade::clearResolvedInstance('request');

        if ($originalRequest !== null) {
            $originalRequest->setRouteResolver(
                $originalRouteResolver ?? ($originalRoute ? fn() => $originalRoute : null)
            );
        }

        $this->restoreRouter($app, $originalRoute);
    }

    private function restoreRouter($app, $originalRoute): void
    {
        if (!$app->bound('router') || $originalRoute === null) {
            return;
        }

        $router = $app['router'];

        if (method_exists($router, 'setCurrentRoute')) {
            $router->setCurrentRoute($originalRoute);
            return;
        }

        try {
            $reflection = new ReflectionObject($router);
            if ($reflection->hasProperty('current')) {
                $property = $reflection->getProperty('current');
                $property->setValue($router, $originalRoute);
            }
        } catch (Throwable) {
            // Ignore if router cannot be reset
        }
    }

    /**
     * @throws ApiException
     */
    private function assertSuccessful(): void
    {
        if ($this->response->isSuccessful()) {
            return;
        }

        throw ApiException::fromResponse(
            status: $this->response->getStatusCode(),
            message: $this->data['message'] ?? null,
            errors: $this->data['errors'] ?? []
        );
    }
}
