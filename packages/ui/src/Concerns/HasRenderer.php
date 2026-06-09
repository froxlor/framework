<?php

namespace Froxlor\UI\Concerns;

use Froxlor\UI\Resources\Resource;
use Illuminate\View\View;

trait HasRenderer
{
    /**
     * Render a resource method (like index, create, edit, etc.) and return the view.
     *
     * @param string $resource The resource class name, e.g. App\Resources\UserResource::class
     * @param string $method The method to call on the resource, e.g. 'index', 'create', 'edit'
     * @param array $attributes Additional attributes to pass to the view
     */
    public static function render(string $resource, string $method, array $attributes = []): View
    {
        return Resource::render($resource, $method, $attributes);
    }
}
