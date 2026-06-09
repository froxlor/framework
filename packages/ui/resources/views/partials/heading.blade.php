@if($resource->teaser || $resource->title || $resource->description || $resource->actions)
    <x-ui::heading>
        <div>
            @if($resource->teaser)
                <x-ui::teaser>{{ $resource->teaser }}</x-ui::teaser>
            @endif
            @if($resource->title)
                <x-ui::title>{{ $resource->title }}</x-ui::title>
            @endif
            @if($resource->description)
                <x-ui::subtitle>{{ $resource->description }}</x-ui::subtitle>
            @endif
        </div>
        <x-slot:actions>
            @foreach($resource->actions ?? [] as $action)
                @php($actionObj = is_array($action) ? (object)$action : $action)
                @livewire($actionObj->view, ['action' => $actionObj])
            @endforeach
        </x-slot>
    </x-ui::heading>
@endif
