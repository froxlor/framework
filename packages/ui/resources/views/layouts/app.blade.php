<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \Froxlor\Core\Support\Setting::get('ui.theme') }}" data-ui-pending>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <!-- Title -->
    <title>{{ $title }}</title>

    <!-- CSRF Token -->
    <meta name="csrf_token" value="{{ csrf_token() }}"/>

    <!-- Assets -->
    @froxlorHead
    @livewireStyles
</head>
<x-ui::body :class="$bodyClasses" :sub-classes="$bodySubClasses">
    <x-ui::alert.status :status="session('message')"/>
    {{ $slot }}
    @livewireScripts
    <script>
        (() => {
            window.froxlorHighlightCodeBlocks ??= () => {
                if (!window.hljs) {
                    return;
                }

                document.querySelectorAll('pre code[class*="language-"]:not(.hljs)').forEach((element) => {
                    window.hljs.highlightElement(element);
                });
            };

            if (!window.froxlorHighlightListenersBound) {
                document.addEventListener('DOMContentLoaded', window.froxlorHighlightCodeBlocks);
                document.addEventListener('livewire:navigated', window.froxlorHighlightCodeBlocks);
                window.froxlorHighlightListenersBound = true;
            }

            window.froxlorHighlightCodeBlocks();
        })();
    </script>
</x-ui::body>
</html>
