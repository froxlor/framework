<?php

namespace Froxlor\UI\Schemas;

use Froxlor\UI\Concerns\HasComponent;
use Froxlor\UI\Concerns\HasFetch;
use Froxlor\UI\Concerns\HasProps;
use Froxlor\UI\Concerns\HasPush;
use Froxlor\UI\Concerns\HasStacker;
use Froxlor\UI\Contracts\ResourceComponent;
use Froxlor\UI\Support\RouteAttributeNormalizer;

/**
 * @property ?array $filters
 * @property ?object $intended
 */
class Schema extends ResourceComponent
{
    use HasComponent, HasFetch, HasProps, HasPush, HasStacker;

    protected array $fillable = [
        'key', 'title', 'description', 'teaser', 'actions', 'schema', 'props',
        'push', 'fetch', 'cols', 'grid_cols', 'filters', 'intended', 'redirectFirst',
    ];

    public int $cols = 1;

    public ?string $grid_cols = null;

    public ?array $filters = null;

    public mixed $intended = null;

    public string $view = 'ui::schema.schema';

    public function cols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    public function gridCols(string $gridCols): static
    {
        $this->grid_cols = $gridCols;

        return $this;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function intendedRoute(string $route, array $attributes = []): static
    {
        $this->intended = (object)[
            'route' => $route,
            'attributes' => RouteAttributeNormalizer::normalize($attributes),
        ];

        return $this;
    }
}
