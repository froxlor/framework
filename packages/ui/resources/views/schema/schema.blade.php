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

        $this->redirect($intended);
    }

    private function setDataRecursively(array $items, array $data = []): array
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
                foreach ($this->setDataRecursively($item->schema, $data) as $k => $v) {
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
