@props(['status' => null])

@if($status)
    <x-ui::alert variant="{{ $status[0] }}" layout="solid" format="square">
        <x-ui::icon name="user"/>
        <x-ui::alert.title>{{ $status[1] }}</x-ui::alert.title>
    </x-ui::alert>
@endif
