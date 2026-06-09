@props(['name' => 'loader-circle'])

<x-ui::icon :name="$name" {{ $attributes->twMerge('animate-spin') }} />
