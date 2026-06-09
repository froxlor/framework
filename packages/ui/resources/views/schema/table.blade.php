<?php

use Laravel\SerializableClosure\SerializableClosure;
use Froxlor\UI\Support\UrlResolver;
use Froxlor\UI\Tables\Table as TableResource;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

new class extends Component {
    public mixed $resource;

    public array $rows = [];

    public function mount(mixed $resource): void
    {
        $this->resource = $resource instanceof TableResource
            ? $resource
            : new TableResource()->fill((array)$resource);

        $this->resource->normalizeArrayPropsToObjects();
        $this->rows = $this->resource->getData();

        $this->registerActionHandlers();
        $this->resolveVisibleActions();
        $this->stripCallables();
        $this->maybeRedirectFirst();
    }

    public function runBulkAction(string $actionKey, array $selected = []): void
    {
        $action = collect($this->resource->bulkActions ?? [])
            ->first(fn ($item) => ($item->key ?? null) === $actionKey);

        if (!$action) {
            return;
        }

        $handler = $this->resolveActionHandler($action->handler_token ?? null);

        if (!$this->hasExecutableHandler($handler)) {
            return;
        }

        $selectionKey = (string) ($this->resource->selectionKey ?? 'id');
        $selected = array_values(array_unique(array_map('strval', $selected)));
        $selectedRows = array_values(array_filter($this->rows, function (array $row) use ($selected, $selectionKey) {
            $value = data_get($row, $selectionKey);

            return $value !== null && in_array((string) $value, $selected, true);
        }));

        $result = $this->runResolvedBulkActionHandler(
            $handler,
            $selected,
            $selectedRows,
            $selectionKey,
            $action
        );

        if (is_string($result) && $result !== '') {
            $this->redirect($result, navigate: true);

            return;
        }

        $this->rows = $this->resource->getData();
        $this->dispatch('close');
        $this->dispatch('table-selection-cleared', key: $this->resource->key ?? 'main');
    }

    private function registerActionHandlers(): void
    {
        foreach ($this->resource->bulkActions ?? [] as $action) {
            if (!is_object($action) || !isset($action->handler) || !$this->hasExecutableHandler($action->handler)) {
                continue;
            }

            $token = $this->makeActionHandlerToken($action);
            $serializedHandler = $this->serializeActionHandler($action->handler);

            if ($serializedHandler === []) {
                continue;
            }

            session()->put(
                $this->actionHandlerSessionKey($token),
                $serializedHandler
            );

            $action->handler_token = $token;
        }
    }

    private function resolveVisibleActions(): void
    {
        if (!is_iterable($this->resource->columnActions ?? null)) {
            return;
        }

        $this->rows = array_map(function (array $row) {
            foreach ($this->resource->columnActions as $action) {
                $visible = $action->visible ?? true;

                if (!is_callable($visible)) {
                    continue;
                }

                data_set(
                    $row,
                    '__visible_actions.' . ($action->key ?? ''),
                    (bool)app()->call($visible, [
                        'row' => $row,
                        'action' => $action,
                        'resource' => $this->resource,
                    ])
                );
            }

            return $row;
        }, $this->rows);
    }

    private function stripCallables(): void
    {
        $this->resource->bulkActions = array_map(function ($action) {
            if (is_object($action) && isset($action->handler) && $this->hasExecutableHandler($action->handler)) {
                $action->handler = null;
            }

            return $action;
        }, $this->resource->bulkActions ?? []);

        $this->resource->columnActions = array_map(function ($action) {
            if (is_object($action) && isset($action->visible) && is_callable($action->visible)) {
                $action->visible = true;
            }

            return $action;
        }, $this->resource->columnActions ?? []);

        $this->resource->columns = array_map(function ($column) {
            if (is_object($column) && isset($column->formatValue) && is_callable($column->formatValue)) {
                $column->formatValue = null;
            }

            return $column;
        }, $this->resource->columns ?? []);
    }

    private function maybeRedirectFirst(): void
    {
        if (!$this->resource->shouldRedirectFirst() || !count($this->rows)) {
            return;
        }

        $url = UrlResolver::resolve($this->resource->intended, $this->rows[0]);

        if ($url) {
            $this->redirect($url);
        }
    }

    private function makeActionHandlerToken(object $action): string
    {
        return sha1(implode('|', [
            request()->path(),
            $this->resource->key ?? 'main',
            $action->key ?? 'action',
        ]));
    }

    private function actionHandlerSessionKey(string $token): string
    {
        return 'froxlor.ui.table.bulk_action_handlers.' . $token;
    }

    private function serializeActionHandler(mixed $handler): array
    {
        if ($this->isSerializedActionHandler($handler)) {
            return $handler;
        }

        if ($handler instanceof \Closure) {
            return [
                '__serialized_action_handler' => true,
                'type' => 'closure',
                'value' => serialize(new SerializableClosure($handler)),
            ];
        }

        if ((is_string($handler) && $handler !== '') || (is_array($handler) && is_callable($handler))) {
            return [
                '__serialized_action_handler' => true,
                'type' => 'callable',
                'value' => $handler,
            ];
        }

        return [];
    }

    private function hasExecutableHandler(mixed $handler): bool
    {
        if ($this->isSerializedActionHandler($handler)) {
            return true;
        }

        return $handler instanceof \Closure
            || (is_string($handler) && $handler !== '')
            || (is_array($handler) && is_callable($handler))
            || is_callable($handler);
    }

    private function runResolvedBulkActionHandler(
        mixed $handler,
        array $selected,
        array $selectedRows,
        string $selectionKey,
        mixed $action
    ): mixed {
        $modelParameter = $this->resolveBulkActionModelParameter($handler);

        if (!$modelParameter) {
            return app()->call($handler, [
                'selected' => $selected,
                'selectedKeys' => $selected,
                'rows' => $selectedRows,
                'selectedRows' => $selectedRows,
                'action' => $action,
                'resource' => $this->resource,
                'component' => $this,
            ]);
        }

        [$parameterName, $modelClass] = $modelParameter;
        $models = $this->resolveSelectedModels($modelClass, $selected, $selectionKey);
        $result = null;

        foreach ($selected as $selectedKey) {
            $model = $models[(string) $selectedKey] ?? null;

            if (!$model instanceof Model) {
                continue;
            }

            $result = app()->call($handler, [
                $parameterName => $model,
                'selected' => $selected,
                'selectedKeys' => $selected,
                'rows' => $selectedRows,
                'selectedRows' => $selectedRows,
                'action' => $action,
                'resource' => $this->resource,
                'component' => $this,
            ]);
        }

        return $result;
    }

    private function resolveBulkActionModelParameter(mixed $handler): ?array
    {
        $reflection = match (true) {
            $handler instanceof \Closure => new \ReflectionFunction($handler),
            is_array($handler) && count($handler) === 2 => new \ReflectionMethod($handler[0], $handler[1]),
            is_string($handler) && str_contains($handler, '@') => new \ReflectionMethod(...explode('@', $handler, 2)),
            is_string($handler) && method_exists($handler, '__invoke') => new \ReflectionMethod($handler, '__invoke'),
            is_object($handler) && method_exists($handler, '__invoke') => new \ReflectionMethod($handler, '__invoke'),
            default => null,
        };

        if (!$reflection) {
            return null;
        }

        $parameter = $reflection->getParameters()[0] ?? null;
        $type = $parameter?->getType();

        if (!$parameter || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $typeName = $type->getName();

        if (!is_a($typeName, Model::class, true)) {
            return null;
        }

        return [$parameter->getName(), $typeName];
    }

    private function resolveSelectedModels(string $modelClass, array $selected, string $selectionKey): array
    {
        return $modelClass::query()
            ->whereIn($selectionKey, $selected)
            ->get()
            ->keyBy(fn (Model $model) => (string) data_get($model, $selectionKey))
            ->all();
    }

    private function resolveActionHandler(?string $token): mixed
    {
        if (!$token) {
            return null;
        }

        $stored = session()->get($this->actionHandlerSessionKey($token));

        if (!is_array($stored)) {
            return null;
        }

        return $this->deserializeActionHandler($stored);
    }

    private function isSerializedActionHandler(mixed $handler): bool
    {
        return is_array($handler) && ($handler['__serialized_action_handler'] ?? false) === true;
    }

    private function deserializeActionHandler(mixed $handler): mixed
    {
        if (!$this->isSerializedActionHandler($handler)) {
            return $handler;
        }

        if (($handler['type'] ?? null) === 'closure' && is_string($handler['value'] ?? null)) {
            return unserialize($handler['value'])->getClosure();
        }

        return $handler['value'] ?? null;
    }
};
?>

<div class="space-y-8">
    @include('ui::partials.heading', [$resource])

    @php
        $allColumns = collect($resource->columns ?? [])->values();
        $bulkActions = collect($resource->bulkActions ?? [])->filter()->values();
        $columnActions = collect($resource->columnActions ?? [])->filter()->values();
        $searchableColumns = $allColumns->filter(fn($c) => (bool) ($c->searchable ?? false))->values();
        $toggleableColumns = $allColumns->filter(fn($c) => (bool) ($c->toggleable ?? false))->values();
        $toggleableColumnKeys = $toggleableColumns->map(fn($c) => $c->key ?? null)->filter()->values()->all();
        $selectionEnabled = (bool) ($resource->selectable ?? false);
        $selectionKey = (string) ($resource->selectionKey ?? 'id');
        $selectionInputName = (string) ($resource->selectionInputName ?? 'selected');
        $selectionFieldName = str_ends_with($selectionInputName, '[]')
            ? $selectionInputName
            : $selectionInputName . '[]';

        $defaultVisibleToggleable = $toggleableColumns
            ->filter(fn($c) => !($c->isHiddenByDefault ?? false))
            ->map(fn($c) => $c->key ?? null)
            ->filter()
            ->values()
            ->all();

        $visibleQuery = request()->query('visible', []);
        if (is_string($visibleQuery)) {
            $visibleQuery = array_filter(array_map('trim', explode(',', $visibleQuery)));
        }
        $visibleQuery = array_values(
            array_filter((array) $visibleQuery, fn($k) => in_array($k, $toggleableColumnKeys, true))
        );

        $visibleToggleableKeys = request()->query('toggleable_state') !== null
            ? $visibleQuery
            : $defaultVisibleToggleable;

        $searchQuery = http_build_query(request()->except(['search', 'page']));
        $toggleQuery = http_build_query(request()->except(['visible', 'toggleable_state', 'page']));

        $searchAction = request()->url() . ($searchQuery !== '' ? "?{$searchQuery}" : '');
        $toggleAction = request()->url() . ($toggleQuery !== '' ? "?{$toggleQuery}" : '');

        $visibleColumns = $allColumns->filter(function ($column) use ($visibleToggleableKeys) {
            return !($column->toggleable ?? false)
                || in_array($column->key ?? '', $visibleToggleableKeys, true);
        })->values();

        $currentSortBy = request()->query('sort_by');
        $currentSortDirection = strtolower((string) request()->query('sort_direction', 'asc'));
        $selectableRowKeys = collect($rows)
            ->map(fn($row) => data_get($row, $selectionKey))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    @endphp

    @if($searchableColumns->count() || $toggleableColumns->count())
        <div class="flex justify-end gap-2 flex-wrap">
            @if($toggleableColumns->count())
                <x-ui::dropdown width="48" contentClasses="p-3 bg-zinc-900" :closeOnContentClick="false">
                    <x-slot name="trigger">
                        <x-ui::button variant="ghost" icon="columns-3">
                            {{ trans('froxlor-core::generic.columns') }}
                        </x-ui::button>
                    </x-slot>

                    <x-slot name="content">
                        <form class="space-y-3" method="GET" action="{{ $toggleAction }}">
                            <input type="hidden" name="toggleable_state" value="1">
                            <div class="space-y-2">
                                @foreach($toggleableColumns as $column)
                                    <label class="flex items-center gap-2 text-sm text-zinc-100 px-1 py-1">
                                        <input
                                            type="checkbox"
                                            name="visible[]"
                                            value="{{ $column->key }}"
                                            @checked(in_array($column->key ?? '', $visibleToggleableKeys, true))
                                        >
                                        <span>{{ $column->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-ui::dropdown.divider/>
                            <div class="pt-1">
                                <x-ui::button as="button" type="submit" size="xs">
                                    {{ trans('froxlor-core::generic.apply') }}
                                </x-ui::button>
                            </div>
                        </form>
                    </x-slot>
                </x-ui::dropdown>
            @endif
            @if($searchableColumns->count())
                <form class="flex items-end gap-2" method="GET" action="{{ $searchAction }}">
                    <x-ui::field>
                        <x-ui::input
                            type="text"
                            name="search"
                            :value="request()->query('search')"
                            placeholder="{{ trans('froxlor-core::generic.search') }}"
                        />
                    </x-ui::field>
                    <x-ui::button as="button" type="submit">
                        {{ trans('froxlor-core::generic.apply') }}
                    </x-ui::button>
                </form>
            @endif
        </div>
    @endif
    {{-- table --}}
    <div
        x-data='{
            selectedRows: [],
            allRowKeys: @json($selectableRowKeys),
            openDialog(name) {
                $dispatch("open-dialog", name);
            },
            async triggerBulkAction(key, closeAfter = false) {
                await $wire.runBulkAction(key, [...this.selectedRows]);

                if (closeAfter) {
                    $dispatch("close");
                }
            },
            isAllSelected() {
                return this.allRowKeys.length > 0 && this.selectedRows.length === this.allRowKeys.length;
            },
            toggleAllRows(checked) {
                this.selectedRows = checked ? [...this.allRowKeys] : [];
            }
        }'
        x-on:table-selection-cleared.window="if (!$event.detail?.key || $event.detail.key === @js($resource->key ?? 'main')) selectedRows = []"
        class="space-y-3"
    >
    @if($bulkActions->isNotEmpty())
        <div
            x-cloak
            x-show="selectedRows.length > 0"
            x-transition.opacity.duration.150ms
            class="flex items-center justify-between gap-3 flex-wrap rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]"
        >
            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                <span x-text="selectedRows.length"></span>
                {{ trans('froxlor-ui::generic.items_selected') }}
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @foreach($bulkActions as $action)
                    @php
                        $icon = $action->icon ?? null;
                        $iconName = is_object($icon) ? ($icon->name ?? null) : (is_array($icon) ? ($icon['name'] ?? null) : $icon);
                        $method = strtoupper((string) ($action->method ?? 'GET'));
                        $confirm = $action->confirm ?? null;
                        $hasConfirm = (bool) $confirm;
                        $confirmData = is_array($confirm) ? $confirm : [];
                        $isDestructive = (bool) ($action->destructive ?? false);
                        $confirmTitle = $confirmData['title'] ?? ($action->label ?? trans($isDestructive
                            ? 'froxlor-ui::generic.confirm_destructive_bulk_action_title'
                            : 'froxlor-ui::generic.confirm_bulk_action_title'));
                        $confirmDescription = is_string($confirm)
                            ? $confirm
                            : ($confirmData['description'] ?? trans($isDestructive
                                ? 'froxlor-ui::generic.confirm_destructive_bulk_action_description'
                                : 'froxlor-ui::generic.confirm_bulk_action_description'));
                        $confirmLabel = $confirmData['confirm_label'] ?? ($action->label ?? trans('froxlor-ui::generic.confirm'));
                        $cancelLabel = $confirmData['cancel_label'] ?? trans('froxlor-ui::generic.cancel');
                        $dialogName = 'bulk-action-' . ($resource->key ?? 'main') . '-' . ($action->key ?? 'action');
                        $formId = 'bulk-action-form-' . ($resource->key ?? 'main') . '-' . ($action->key ?? 'action');
                    @endphp
                    @if($action->visible ?? true)
                        @if(!empty($action->handler_token))
                            @if($hasConfirm)
                                <x-ui::button
                                    type="button"
                                    :icon="$iconName"
                                    :variant="$action->variant ?? 'secondary'"
                                    data-dialog-name="{{ $dialogName }}"
                                    x-on:click.prevent="openDialog($el.dataset.dialogName)"
                                    wire:loading.attr="disabled"
                                    x-bind:disabled="selectedRows.length === 0"
                                >
                                    {{ $action->label ?? 'NA' }}
                                </x-ui::button>
                                <x-ui::dialog :name="$dialogName" maxWidth="md">
                                    <x-ui::card>
                                        <x-ui::card.header>
                                            <x-ui::card.title>{{ $confirmTitle }}</x-ui::card.title>
                                            <x-ui::card.description>
                                                {{ $confirmDescription }}
                                                <span class="font-medium" x-text="'(' + selectedRows.length + ')'"></span>
                                            </x-ui::card.description>
                                        </x-ui::card.header>
                                        <x-ui::card.footer class="flex justify-end gap-2">
                                            <x-ui::button variant="ghost" x-on:click="$dispatch('close')">
                                                {{ $cancelLabel }}
                                            </x-ui::button>
                                            <x-ui::button
                                                type="button"
                                                :icon="$iconName"
                                                :variant="$action->variant ?? 'secondary'"
                                                data-action-key="{{ $action->key }}"
                                                x-on:click.prevent="triggerBulkAction($el.dataset.actionKey, true)"
                                            >
                                                {{ $confirmLabel }}
                                            </x-ui::button>
                                        </x-ui::card.footer>
                                    </x-ui::card>
                                </x-ui::dialog>
                            @else
                                <x-ui::button
                                    type="button"
                                    :icon="$iconName"
                                    :variant="$action->variant ?? 'secondary'"
                                    data-action-key="{{ $action->key }}"
                                    x-on:click.prevent="triggerBulkAction($el.dataset.actionKey)"
                                    x-bind:disabled="selectedRows.length === 0"
                                >
                                    {{ $action->label ?? 'NA' }}
                                </x-ui::button>
                            @endif
                        @else
                            <form id="{{ $formId }}" method="{{ $method === 'GET' ? 'GET' : 'POST' }}" action="{{ $action->href ?? '#' }}">
                                @if($method !== 'GET')
                                    @csrf
                                    @method($action->method)
                                @endif
                                <template x-for="selected in selectedRows" :key="selected">
                                    <input type="hidden" name="{{ $selectionFieldName }}" :value="selected">
                                </template>
                                @if($hasConfirm)
                                    <x-ui::button
                                        type="button"
                                        :icon="$iconName"
                                        :variant="$action->variant ?? 'secondary'"
                                        data-dialog-name="{{ $dialogName }}"
                                        x-on:click.prevent="openDialog($el.dataset.dialogName)"
                                        x-bind:disabled="selectedRows.length === 0"
                                    >
                                        {{ $action->label ?? 'NA' }}
                                    </x-ui::button>
                                @else
                                    <x-ui::button :icon="$iconName" :variant="$action->variant ?? 'secondary'" x-bind:disabled="selectedRows.length === 0">
                                        {{ $action->label ?? 'NA' }}
                                    </x-ui::button>
                                @endif
                            </form>
                            @if($hasConfirm)
                                <x-ui::dialog :name="$dialogName" maxWidth="md">
                                    <x-ui::card>
                                        <x-ui::card.header>
                                            <x-ui::card.title>{{ $confirmTitle }}</x-ui::card.title>
                                            <x-ui::card.description>
                                                {{ $confirmDescription }}
                                                <span class="font-medium" x-text="'(' + selectedRows.length + ')'"></span>
                                            </x-ui::card.description>
                                        </x-ui::card.header>
                                        <x-ui::card.footer class="flex justify-end gap-2">
                                            <x-ui::button variant="ghost" x-on:click="$dispatch('close')">
                                                {{ $cancelLabel }}
                                            </x-ui::button>
                                            <x-ui::button
                                                type="button"
                                                :icon="$iconName"
                                                :variant="$action->variant ?? 'secondary'"
                                                x-on:click.prevent="document.getElementById('{{ $formId }}')?.submit()"
                                            >
                                                {{ $confirmLabel }}
                                            </x-ui::button>
                                        </x-ui::card.footer>
                                    </x-ui::card>
                                </x-ui::dialog>
                            @endif
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    <x-ui::card class="py-0">
        <x-ui::card.content class="px-0">
            <table class="min-w-full divide-y-2 divide-zinc-200 dark:divide-white/10">
                <thead>
                <tr>
                    @if($selectionEnabled)
                        <th class="w-12 p-4 text-left">
                            <x-ui::input.checkbox
                                container-class="justify-center"
                                x-bind:checked="isAllSelected()"
                                x-bind:indeterminate="selectedRows.length > 0 && !isAllSelected()"
                                x-on:change="toggleAllRows($event.target.checked)"
                            />
                        </th>
                    @endif
                    @foreach($visibleColumns as $column)
                        <th class="p-4 text-left">
                            @if($column->sortable ?? false)
                                @php
                                    $isCurrentSort = $currentSortBy === ($column->key ?? null);
                                    $isAsc = $isCurrentSort && $currentSortDirection === 'asc';
                                    $isDesc = $isCurrentSort && $currentSortDirection === 'desc';
                                    $nextSortBy = $isDesc ? null : $column->key;
                                    $nextSortDirection = $isAsc  ? 'desc' : ($isDesc ? null : 'asc');

                                    $sortQuery = request()->query();
                                    $sortQuery['sort_by'] = $nextSortBy;
                                    $sortQuery['sort_direction'] = $nextSortDirection;
                                    if ($nextSortBy === null) {
                                        unset($sortQuery['sort_by'], $sortQuery['sort_direction']);
                                    }
                                @endphp
                                <a class="inline-flex items-center gap-1 hover:opacity-80" wire:navigate href="{{ request()->url() . '?' . http_build_query($sortQuery) }}">
                                    <span>{{ $column->label }}</span>
                                    <x-ui::icon
                                        :name="$isAsc ? 'chevron-up' : 'chevron-down'"
                                        :variant="$isCurrentSort ? 'primary' : 'subtle'"
                                    />
                                </a>
                            @else
                                {{ $column->label }}
                            @endif
                        </th>
                    @endforeach
                    @if($columnActions->isNotEmpty())
                        <th class="p-4 text-right">{{ trans('froxlor-ui::generic.actions') }}</th>
                    @endif
                </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse($rows as $rowKey => $row)
                    @php($rowSelectionValue = data_get($row, $selectionKey))
                    <tr class="group" x-bind:class="selectedRows.includes(@js((string) $rowSelectionValue)) ? 'bg-primary/5' : ''">
                        @if($selectionEnabled)
                            <td class="px-4 py-3 align-middle">
                                @if($rowSelectionValue !== null && $rowSelectionValue !== '')
                                    <x-ui::input.checkbox
                                        :value="$rowSelectionValue"
                                        container-class="justify-center"
                                        x-model="selectedRows"
                                    />
                                @endif
                            </td>
                        @endif
                        {{-- columns --}}
                        @foreach ($visibleColumns as $columnKey => $column)
                            @php($value = data_get($row, $column->key))
                            @livewire($column->view, ['resource' => $resource, 'column' => $column, 'row' => $row, 'value' => $value], $rowKey . '-column-' . $columnKey)
                        @endforeach
                        {{-- actions --}}
                        @if($columnActions->isNotEmpty())
                            <td class="px-4 py-3 text-right">
                                @foreach ($columnActions as $actionKey => $action)
                                    @livewire($action->view, ['resource' => $resource, 'action' => $action, 'row' => $row, 'value' => $value], $rowKey . '-action-' . $actionKey)
                                @endforeach
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $visibleColumns->count() + ($columnActions->isNotEmpty() ? 1 : 0) + ($selectionEnabled ? 1 : 0) }}" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ trans('froxlor-core::generic.no_entries') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </x-ui::card.content>
    </x-ui::card>
    </div>

    {{-- pagination --}}
    @if(is_array($resource->pagination ?? null) && (($resource->pagination['total'] ?? 1) > 1))
        <x-ui::pagination
            :current="$resource->pagination['current'] ?? 1"
            :total="$resource->pagination['total'] ?? 1"
        />
    @endif
</div>
