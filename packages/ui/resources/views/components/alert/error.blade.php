@props(['messages'])

@if ($messages)
    <x-ui::alert variant="danger">
        <x-ui::icon name="circle-alert"/>
        <x-ui::alert.title>
            <ul>
                @foreach ((array) $messages as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </x-ui::alert.title>
    </x-ui::alert>
@endif
