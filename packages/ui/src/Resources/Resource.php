<?php

namespace Froxlor\UI\Resources;

use Froxlor\UI\Contracts\ResourceComponent;
use Illuminate\View\View;

class Resource
{
    /**
     * Renders a specific method on a resource and returns the appropriate view.
     */
    public static function render(string $resource, string $method, array $attributes = []): View
    {
        if (!is_a($resource, Resource::class, true)) {
            abort(599, "Resource {$resource} is not a valid resource");
        }

        if (!method_exists($resource, $method)) {
            abort(599, "Method {$method} does not exist on resource {$resource}");
        }

        $renderable = app($resource)->{$method}(...array_values($attributes));

        return self::resourceResponse($renderable, $attributes);
    }

    /**
     * Returns a view for the given resource component.
     */
    protected static function resourceResponse(ResourceComponent $resource, array $attributes = []): View
    {
        $resource = $resource->props($attributes)->normalizeArrayPropsToObjects();

        return view('ui::schema.resource', [
            'resource' => $resource,
        ]);
    }
}
