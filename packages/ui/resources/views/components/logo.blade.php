@props(['src' => asset(\Froxlor\Core\Support\Setting::get('ui.logo', 'vendor/froxlor/ui/assets/img/icon.png'))])

<img src="{{ $src }}" alt="logo" {{ $attributes }} {{ $attributes->twMerge('fill-current text-gray-500') }}/>
