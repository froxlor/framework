<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \Froxlor\Core\Support\Setting::get('appearance.theme') }}" data-ui-pending>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <!-- Title -->
    <title>{{ $title }}</title>

    <!-- CSRF Token -->
    <meta name="csrf_token" value="{{ csrf_token() }}"/>

    <!-- Hide the page until @tailwindcss/browser's compiled output lands, to avoid an unstyled flash -->
    <style>html[data-ui-pending] body { visibility: hidden; }</style>
    <script>
        (() => {
            const html = document.documentElement;
            const reveal = () => html.removeAttribute('data-ui-pending');

            // Only an untyped <style> added after parsing finishes can be @tailwindcss/browser's
            // output; Livewire's own untyped <style> block (and ours above) land before that.
            const isCandidate = (node) => node.nodeType === Node.ELEMENT_NODE
                && node.tagName === 'STYLE'
                && !node.hasAttribute('type');

            let armed = document.readyState !== 'loading';
            const ignored = new Set();

            const waitForContent = (node) => {
                if (node.textContent.length > 0) {
                    reveal();
                    return;
                }

                const contentObserver = new MutationObserver(() => {
                    if (node.textContent.length > 0) {
                        reveal();
                        contentObserver.disconnect();
                    }
                });
                contentObserver.observe(node, {childList: true, characterData: true, subtree: true});
            };

            const headObserver = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    for (const node of mutation.addedNodes) {
                        if (!isCandidate(node)) {
                            continue;
                        }

                        if (!armed) {
                            ignored.add(node);
                            continue;
                        }

                        if (ignored.has(node)) {
                            continue;
                        }

                        headObserver.disconnect();
                        waitForContent(node);
                        return;
                    }
                }
            });

            headObserver.observe(document.head, {childList: true});

            if (!armed) {
                document.addEventListener('readystatechange', function onReadyStateChange() {
                    if (document.readyState !== 'loading') {
                        armed = true;
                        document.removeEventListener('readystatechange', onReadyStateChange);
                    }
                });
            }

            window.addEventListener('load', reveal);
            setTimeout(reveal, 1500);

            // wire:navigate copies data-ui-pending back onto <html> from each fetched page
            document.addEventListener('livewire:navigated', reveal);
        })();
    </script>

    <!-- Assets -->
    @froxlorHead
    @livewireStyles
</head>
<x-ui::body :class="$bodyClasses" :sub-classes="$bodySubClasses">
    <x-ui::alert.status :status="session('message')"/>
    <x-ui::toast/>
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
