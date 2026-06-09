@props(['href'])

<x-ui::link :href="$href" {{ $attributes->twMerge('text-gray-600 hover:no-underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-white') }}>
    {{ $slot }}
</x-ui::link>
