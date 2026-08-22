<?php

namespace Froxlor\UI\Concerns;

use Froxlor\Core\Support\Api;
use Froxlor\UI\Exceptions\ApiException;

trait HasFetch
{
    public ?object $fetch = null;

    public function fetch(string $url, string $method = 'GET'): static
    {
        $this->fetch = (object)[
            'url' => $url,
            'method' => strtoupper($method),
        ];

        return $this;
    }

    /**
     * @throws ApiException
     */
    public function getData(): array
    {
        if (!$this->fetch) {
            return [];
        }

        $payload = $this->props ?? [];
        $columns = $this->getColumns();

        $tablePayload = $this->buildTablePayload($payload, $columns);

        data_set($payload, '_table', array_merge(
            data_get($payload, '_table', []),
            $tablePayload
        ));

        $this->applyPaginationToPayload($payload, $tablePayload);

        $response = $this->callApi($payload);

        [$rows, $meta, $links] = $this->extractResponse($response);

        $this->hydratePagination($meta, $links);

        if (!$columns) {
            return $rows;
        }

        $rows = $this->formatRows($rows, $columns);

        if (!$meta) {
            $rows = $this->applyLocalSearchAndSort($rows, $tablePayload);
        }

        return $rows;
    }

    protected function getColumns(): array
    {
        if (!property_exists($this, 'columns') || !is_iterable($this->columns)) {
            return [];
        }

        return is_array($this->columns)
            ? $this->columns
            : iterator_to_array($this->columns);
    }

    protected function buildTablePayload(array $payload, array $columns): array
    {
        $normalized = array_values(array_filter(array_map(
            fn($col) => $this->normalizeColumn($col),
            $columns
        )));

        return [
            'columns' => $normalized,
            'search' => data_get($payload, '_table.search', request()->query('search')),
            'sort_by' => data_get($payload, '_table.sort_by', request()->query('sort_by')),
            'sort_direction' => data_get($payload, '_table.sort_direction', request()->query('sort_direction', 'asc')),
            'page' => data_get($payload, '_table.page', request()->query('page')),
            'limit' => data_get($payload, '_table.limit', request()->query('limit')),
            'searchable_columns' => $this->getSearchableColumns($columns),
        ];
    }

    protected function normalizeColumn(mixed $column): ?array
    {
        $key = $this->col($column, 'key');

        if (!$key) {
            return null;
        }

        return [
            'key' => $key,
            'sortable' => (bool)$this->col($column, 'sortable'),
            'searchable' => (bool)$this->col($column, 'searchable'),
            'sortUsing' => $this->col($column, 'sortUsing'),
            'searchUsing' => $this->col($column, 'searchUsing'),
            'toggleable' => (bool)$this->col($column, 'toggleable'),
            'isHiddenByDefault' => (bool)$this->col($column, 'isHiddenByDefault'),
            'html' => (bool)$this->col($column, 'html'),
        ];
    }

    protected function getSearchableColumns(array $columns): array
    {
        $searchColumns = [];

        foreach ($columns as $col) {
            if (!$this->col($col, 'searchable')) {
                continue;
            }

            $using = $this->col($col, 'searchUsing') ?: [$this->col($col, 'key')];

            foreach ($using as $column) {
                $searchColumns[] = $column;
            }
        }

        return array_values(array_unique(array_filter($searchColumns)));
    }

    protected function col(mixed $column, string $key, mixed $default = null): mixed
    {
        return is_object($column)
            ? ($column->{$key} ?? $default)
            : (is_array($column)
                ? ($column[$key] ?? $default)
                : $default);
    }

    protected function applyPaginationToPayload(array &$payload, array $table): void
    {
        if (!empty($table['page'])) {
            $payload['page'] = $table['page'];
        }

        if (!empty($table['limit'])) {
            $payload['limit'] = $table['limit'];
        }
    }

    protected function callApi(array $payload): array
    {
        $method = $this->fetch->method;
        $isGet = $method === 'GET';

        return Api::request(
            method: $method,
            uri: $this->fetch->url,
            body: $isGet ? [] : $payload,
            parameters: $isGet ? $payload : [],
            pagination: $this->shouldPaginate()
        )->toArray();
    }

    protected function shouldPaginate(): bool
    {
        return property_exists($this, 'paginate') && $this->paginate;
    }

    protected function extractResponse(array $response): array
    {
        return [
            $response['data'] ?? [],
            is_array($response['meta'] ?? null) ? $response['meta'] : null,
            is_array($response['links'] ?? null) ? $response['links'] : null,
        ];
    }

    protected function hydratePagination(?array $meta, ?array $links): void
    {
        if (!property_exists($this, 'pagination')) {
            return;
        }

        $this->pagination = $meta ? [
            'current' => (int)($meta['current_page'] ?? 1),
            'total' => (int)($meta['last_page'] ?? 1),
            'per_page' => (int)($meta['per_page'] ?? 0),
            'total_items' => (int)($meta['total'] ?? 0),
            'prev' => $links['prev'] ?? null,
            'next' => $links['next'] ?? null,
        ] : null;
    }

    protected function formatRows(array $rows, array $columns): array
    {
        return array_map(function (array $row) use ($columns) {
            foreach ($columns as $column) {
                $key = $this->col($column, 'key');

                if (!$key) {
                    continue;
                }

                // Resolve description/tooltip content from the raw row value first, so they
                // can see the original data (e.g. the full dependency array) even when the
                // column's own formatValue() collapses it into a compact summary below.
                $descriptionLines = $this->resolveDescriptionLines($column, $row, $key);

                if ($descriptionLines !== []) {
                    data_set($row, '__descriptions.' . $key, $descriptionLines);
                }

                $tooltip = $this->resolveTooltip($column, $row, $key);

                if ($tooltip !== null) {
                    data_set($row, '__tooltip.' . $key, $tooltip);
                }

                $formatter = $this->col($column, 'formatValue');

                if (is_callable($formatter)) {
                    data_set($row, $key, app()->call($formatter, [
                        'value' => data_get($row, $key),
                        'row' => $row,
                        'column' => $column,
                    ]));
                }
            }

            return $row;
        }, $rows);
    }

    /**
     * Resolve a column's stacked description lines (see TextColumn::description())
     * into their final, per-row text/markup, dropping any that resolve to nothing.
     */
    protected function resolveDescriptionLines(mixed $column, array $row, string $key): array
    {
        $lines = $this->col($column, 'descriptionLines');

        if (!is_array($lines) || $lines === []) {
            return [];
        }

        return array_values(array_filter(array_map(function ($line) use ($row, $column, $key) {
            $value = is_array($line) ? ($line['value'] ?? null) : null;
            $html = is_array($line) && ($line['html'] ?? false);

            $value = is_callable($value)
                ? app()->call($value, ['value' => data_get($row, $key), 'row' => $row, 'column' => $column])
                : $value;

            if ($value === null || $value === '') {
                return null;
            }

            return ['value' => $value, 'html' => $html];
        }, $lines)));
    }

    /**
     * Resolve a column's tooltip content (see TooltipColumn::tooltip()) for a single
     * row, so wide/long values (e.g. long lists) can render compactly with the full
     * detail available on hover instead of breaking the table layout.
     */
    protected function resolveTooltip(mixed $column, array $row, string $key): ?string
    {
        $tooltip = $this->col($column, 'tooltip');

        if ($tooltip === null || $tooltip === '') {
            return null;
        }

        $tooltip = is_callable($tooltip)
            ? app()->call($tooltip, ['value' => data_get($row, $key), 'row' => $row, 'column' => $column])
            : $tooltip;

        return ($tooltip !== null && $tooltip !== '') ? (string)$tooltip : null;
    }

    protected function applyLocalSearchAndSort(array $rows, array $table): array
    {
        $search = (string)($table['search'] ?? '');
        $sortBy = $table['sort_by'] ?? null;
        $direction = strtolower((string)($table['sort_direction'] ?? 'asc'));
        $searchable = $table['searchable_columns'] ?? [];

        if ($search !== '' && $searchable) {
            $rows = array_values(array_filter($rows, function ($row) use ($search, $searchable) {
                foreach ($searchable as $key) {
                    $value = data_get($row, $key);

                    if (is_scalar($value) && stripos((string)$value, $search) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($sortBy) {
            usort($rows, function ($a, $b) use ($sortBy, $direction) {
                $result = data_get($a, $sortBy) <=> data_get($b, $sortBy);
                return $direction === 'desc' ? -$result : $result;
            });
        }

        return $rows;
    }
}
