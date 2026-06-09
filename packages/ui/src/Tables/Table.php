<?php

namespace Froxlor\UI\Tables;

use Froxlor\UI\Concerns\HasFetch;
use Froxlor\UI\Concerns\HasProps;
use Froxlor\UI\Contracts\ResourceComponent;
use Froxlor\UI\Support\RouteAttributeNormalizer;

/**
 * @property ?array $attributes
 * @property ?array $columns
 * @property ?array $columnActions
 * @property ?array $filters
 * @property ?array $intended
 * @property ?bool $redirectFirst
 */
class Table extends ResourceComponent
{
    use HasFetch, HasProps;

    public array $columns = [];

    public ?array $bulkActions = null;

    public ?array $columnActions = null;

    public ?array $filters = null;

    public mixed $intended = null;

    public bool $paginate = true;

    public ?array $pagination = null;

    public bool $selectable = false;

    public string $selectionKey = 'id';

    public string $selectionInputName = 'selected';

    public string $view = 'ui::schema.table';

    protected array $fillable = [
        'key', 'title', 'description', 'teaser', 'actions', 'schema', 'props',
        'columns', 'bulkActions', 'columnActions', 'filters', 'paginate',
        'pagination', 'intended', 'redirectFirst', 'selectable',
        'selectionKey', 'selectionInputName',
    ];

    public function columns(array $columns): static
    {
        $this->columns = $this->serializeItems($columns, $this->props ?? []);

        return $this;
    }

    public function columnActions(array $columnActions): static
    {
        $this->columnActions = $this->serializeItems($columnActions, $this->props ?? []);

        return $this;
    }

    public function bulkActions(array $bulkActions): static
    {
        $this->bulkActions = $this->serializeItems($bulkActions, $this->props ?? []);
        $this->selectable = true;

        return $this;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    public function selectionKey(string $selectionKey): static
    {
        $this->selectionKey = $selectionKey;

        return $this;
    }

    public function selectionInputName(string $selectionInputName): static
    {
        $this->selectionInputName = $selectionInputName;

        return $this;
    }

    public function intendedRoute(string $route, array $attributes = []): static
    {
        $this->intended = [
            'route' => $route,
            'attributes' => RouteAttributeNormalizer::normalize($attributes),
        ];

        return $this;
    }

    public function toPayload(): array
    {
        $payload = parent::toPayload();

        $payload['column_actions'] = array_map(function ($action) {
            if (is_object($action) && isset($action->visible) && is_callable($action->visible)) {
                $action->visible = true;
            }

            return $action;
        }, $payload['column_actions'] ?? []);

        $payload['columns'] = array_map(function ($column) {
            if (is_object($column) && isset($column->formatValue) && is_callable($column->formatValue)) {
                $column->formatValue = null;
            }

            if (is_object($column) && isset($column->format_value) && is_callable($column->format_value)) {
                $column->format_value = null;
            }

            return $column;
        }, $payload['columns'] ?? []);

        return $payload;
    }

    protected function normalizableProperties(): array
    {
        return array_merge(parent::normalizableProperties(), ['bulkActions']);
    }
}
