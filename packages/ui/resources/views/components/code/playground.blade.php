@props([
    'language' => 'html',
    'code',
])

@php
    if (! function_exists('normalizeIndentation')) {
        function normalizeIndentation(string $code): string
        {
            // Line-Endings vereinheitlichen
            $code = str_replace(["\r\n", "\r"], "\n", $code);

            // führende/abschließende Leerzeilen entfernen
            $code = preg_replace('/^\s*\n|\n\s*$/', '', $code);

            $lines = explode("\n", $code);

            // minimale Einrückung nur über Zeilen ermitteln, die überhaupt eingerückt sind
            $min = null;
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                if (preg_match('/^[ ]+/', $line, $m)) {
                    $indent = strlen($m[0]);
                    $min = $min === null ? $indent : min($min, $indent);
                }
            }

            if ($min && $min > 0) {
                // am Anfang JEDER Zeile bis zu $min Spaces entfernen (multiline)
                $code = preg_replace('/^ {0,' . $min . '}/m', '', $code);
            }

            return $code;
        }
    }

    $language = $language == 'blade' ? 'php' : $language;
@endphp

<div class="rounded-lg bg-card/40 text-card-foreground">
    {{-- Vorschau --}}
    @isset($preview)
        <x-ui::space.y class="p-4 sm:p-8">
            {{ $preview }}
        </x-ui::space.y>
    @endisset

    {{-- Code --}}
    <x-ui::code.fullwidth class="relative">
        <x-ui::button class="absolute top-8 right-8" size="sm" variant="outline">
            <x-ui::icon name="copy"/>
            <x-ui::text>{{ trans('Copy code') }}</x-ui::text>
        </x-ui::button>
        <pre><code class="language-{{ $language }}">{{ normalizeIndentation($code) }}</code></pre>
    </x-ui::code.fullwidth>
</div>
