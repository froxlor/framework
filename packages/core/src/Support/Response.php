<?php

namespace Froxlor\Core\Support;

use Exception;
use Froxlor\Core\Events\Api\CollectionResponseMade;
use Froxlor\Core\Events\Api\ResourceResponseMade;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Response
{
    public static function jsonResource(...$parameters): JsonResource
    {
        $resource = JsonResource::make(...$parameters);
        Event::dispatch(new ResourceResponseMade($resource));
        return $resource;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function jsonResourceCollection($builder): AnonymousResourceCollection
    {
        try {
            $limit = Setting::get('api.pagination_limit', 15);
        } catch (Exception $exception) {
        }

        if ($builder instanceof EloquentBuilder || $builder instanceof QueryBuilder) {
            $search = (string)(request()->input('_table.search') ?? request()->input('search') ?? '');
            $sortBy = request()->input('_table.sort_by') ?? request()->input('sort_by');
            $sortDirection = strtolower((string)(request()->input('_table.sort_direction') ?? request()->input('sort_direction') ?? 'asc'));
            $searchableColumns = request()->input('_table.searchable_columns') ?? request()->input('searchable_columns') ?? [];

            if (is_string($searchableColumns)) {
                $searchableColumns = array_filter(array_map('trim', explode(',', $searchableColumns)));
            }

            if ($search !== '' && is_array($searchableColumns) && count($searchableColumns)) {
                $builder->where(function ($query) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        if (!is_string($column) || !preg_match('/^[a-zA-Z0-9_.-]+$/', $column)) {
                            continue;
                        }

                        $query->orWhere($column, 'like', '%' . $search . '%');
                    }
                });
            }

            if (is_string($sortBy) && preg_match('/^[a-zA-Z0-9_.-]+$/', $sortBy)) {
                $builder->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            }
        }

        if ($perPage = request()->get('limit', $limit ?? 15)) {
            $collection = JsonResource::collection($builder->paginate($perPage));
        } else {
            $collection = JsonResource::collection($builder->get());
        }
        Event::dispatch(new CollectionResponseMade($collection));
        return $collection;
    }
}
