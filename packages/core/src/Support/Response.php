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
use Illuminate\Support\Facades\Schema;
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
            $sortBy = self::resolveSortColumn($sortBy, request()->input('_table.columns'));

            if (is_string($searchableColumns)) {
                $searchableColumns = array_filter(array_map('trim', explode(',', $searchableColumns)));
            }

            if ($search !== '' && is_array($searchableColumns) && count($searchableColumns)) {
                $escapedSearch = addcslashes($search, '\\%_');

                $builder->where(function ($query) use ($escapedSearch, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        if (!is_string($column) || !preg_match('/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)?$/', $column)) {
                            continue;
                        }

                        if (str_contains($column, '.')) {
                            [$relation, $attribute] = explode('.', $column, 2);

                            if (!$query instanceof EloquentBuilder || !method_exists($query->getModel(), $relation)) {
                                continue;
                            }

                            $query->orWhereHas($relation, function ($relationQuery) use ($attribute, $escapedSearch) {
                                $relationQuery->where($attribute, 'like', '%' . $escapedSearch . '%');
                            });

                            continue;
                        }

                        if (!self::builderHasColumn($query, $column)) {
                            continue;
                        }

                        $query->orWhere($column, 'like', '%' . $escapedSearch . '%');
                    }
                });
            }

            if (is_string($sortBy) && preg_match('/^[a-zA-Z0-9_-]+$/', $sortBy) && self::builderHasColumn($builder, $sortBy)) {
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

    /**
     * A column's display key (e.g. "name") may not be a raw database column when it's
     * backed by an accessor composed of several columns. In that case the table's column
     * definition carries a `sortUsing` override naming the real column to sort by instead.
     */
    protected static function resolveSortColumn(mixed $sortBy, mixed $columns): mixed
    {
        if (!is_string($sortBy) || !is_array($columns)) {
            return $sortBy;
        }

        foreach ($columns as $column) {
            if (($column['key'] ?? null) === $sortBy) {
                return $column['sortUsing'] ?? $sortBy;
            }
        }

        return $sortBy;
    }

    /**
     * Guards against ordering/filtering by a column that doesn't exist on the
     * underlying table (e.g. a relation name mistakenly marked searchable/sortable),
     * which would otherwise bubble up as a raw SQL error to the client.
     */
    protected static function builderHasColumn(EloquentBuilder|QueryBuilder $builder, string $column): bool
    {
        $table = $builder instanceof EloquentBuilder ? $builder->getModel()->getTable() : $builder->from;

        try {
            return Schema::hasColumn($table, $column);
        } catch (Exception) {
            return false;
        }
    }
}
