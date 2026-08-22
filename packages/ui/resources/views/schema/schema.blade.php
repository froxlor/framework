<?php

use Froxlor\UI\Exceptions\ApiException;
use Froxlor\UI\Schemas\Schema as SchemaResource;
use Livewire\Component;

new class extends Component {
    public SchemaResource $resource;

    public array $data = [];

    public function mount(SchemaResource $resource): void
    {
        $this->resource = $resource;
        $this->data = $this->setDataRecursively(
            $this->resource->schema,
            $this->resource->getData()
        );
    }

    public function submit(): void
    {
        try {
            $intended = $this->resource->submit($this->data);
        } catch (ApiException $e) {
            $this->setErrorBag($e->getErrors());
            $this->addError('form', $e->getMessage());

            return;
        }

        if ($intended === null) {
            if ($notification = $this->resource->notification) {
                $this->dispatch(
                    'show-toast',
                    title: data_get($notification, 'title'),
                    description: data_get($notification, 'description'),
                    variant: data_get($notification, 'variant'),
                );
            }

            // redirect() normally calls this to avoid a needless re-render; do the same
            // here since we're not navigating away either, just dispatching a toast.
            $this->skipRender();

            return;
        }

        $this->redirect($intended);
    }

    private function setDataRecursively(array $items, array $data = []): array
    {
        $result = [];

        foreach ($this->collectLeaves($items, $data) as $key => $value) {
            data_set($result, $key, $value);
        }

        return $result;
    }

    // wire:model="data.colors.base.color-primary" resolves through nested arrays, so leaf
    // keys (which may themselves contain dots, e.g. "colors.base.color-primary") can't be
    // stored flat — collect them here, then data_set() nests them in setDataRecursively().
    private function collectLeaves(array $items, array $data = []): array
    {
        $result = [];

        foreach ($items as $item) {
            if ($this->isFormComponent($item)) {
                $key = $item->key ?? null;

                if ($key !== null) {
                    $result[$key] = data_get($data, $key, $item->default ?? null);
                }
            }

            if (isset($item->schema) && is_iterable($item->schema)) {
                foreach ($this->collectLeaves($item->schema, $data) as $k => $v) {
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    private function isFormComponent(mixed $item): bool
    {
        return isset($item->view)
            && str_starts_with($item->view, 'ui::schema.')
            && str_contains($item->view, '.components.');
    }
};
?>

<div class="space-y-8">
    @include('ui::partials.heading', [$resource])

    <form wire:submit.prevent="submit">
        <x-ui::space.y>
            @if($errors->isNotEmpty())
                <x-ui::alert.error :messages="$errors->get('form')"/>
            @endif

            @include('ui::schema.partials.render-schema-items', [
                'items' => $resource->schema ?? [],
                'data' => $data,
                'resource' => $resource,
                'cols' => $resource->cols,
                'gap' => 'gap-8',
                'wrapEach' => false,
            ])

            @if($this->resource->push)
                <x-ui::button type="submit">{{ trans('froxlor-ui::generic.submit') }}</x-ui::button>
            @endif
        </x-ui::space.y>
    </form>
</div>
