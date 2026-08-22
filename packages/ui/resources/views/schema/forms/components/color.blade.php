<?php

use Livewire\Component;

new class extends Component
{
    public array $data;

    public object $resource;

    public object $schema;
}
?>

<x-ui::field :col-span="$schema->col ?? 6" data-color-field>
    <div class="flex items-center justify-between gap-2">
        <x-ui::label :for="$schema->key" :value="$schema->label" :required="$schema->required ?? false" />
        @if(!empty($schema->generateShadesFor))
            <button
                type="button"
                class="text-xs font-medium text-primary hover:underline"
                x-data
                x-on:click="window.froxlorGenerateColorShades($el.closest('[data-color-field]'), @js($schema->generateShadesFor))"
            >
                {{ trans('froxlor-ui::generic.generate_shades') }}
            </button>
        @endif
    </div>
    <x-ui::input.color
        :name="$schema->key"
        wire:model="data.{{ $schema->key }}"
    />
    <x-ui::input.error :messages="$errors->get($schema->key)" class="mt-2" />
</x-ui::field>

@if(!empty($schema->generateShadesFor))
    <script>
        window.froxlorGenerateColorShades ??= (fieldEl, shadeTargets) => {
            const sourceInput = fieldEl?.querySelector('input[type="color"]');
            const form = fieldEl?.closest('form');

            if (!sourceInput || !form || !shadeTargets) {
                return;
            }

            const [hue, saturation] = window.froxlorHexToHsl(sourceInput.value);

            for (const [key, lightness] of Object.entries(shadeTargets)) {
                const target = form.querySelector(`input[name="${key}"]`);

                if (!target) {
                    continue;
                }

                target.value = window.froxlorHslToHex(hue, saturation, lightness);
                target.dispatchEvent(new Event('input', {bubbles: true}));
                target.dispatchEvent(new Event('change', {bubbles: true}));
            }
        };

        window.froxlorHexToHsl ??= (hex) => {
            hex = hex.replace('#', '');
            const r = parseInt(hex.substring(0, 2), 16) / 255;
            const g = parseInt(hex.substring(2, 4), 16) / 255;
            const b = parseInt(hex.substring(4, 6), 16) / 255;
            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const l = (max + min) / 2;
            const d = max - min;
            let h = 0;
            let s = 0;

            if (d !== 0) {
                s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

                switch (max) {
                    case r:
                        h = (g - b) / d + (g < b ? 6 : 0);
                        break;
                    case g:
                        h = (b - r) / d + 2;
                        break;
                    default:
                        h = (r - g) / d + 4;
                }

                h *= 60;
            }

            return [h, s * 100];
        };

        window.froxlorHslToHex ??= (h, s, l) => {
            s /= 100;
            l /= 100;

            const c = (1 - Math.abs(2 * l - 1)) * s;
            const x = c * (1 - Math.abs((h / 60) % 2 - 1));
            const m = l - c / 2;
            let [r, g, b] = [0, 0, 0];

            if (h < 60) {
                [r, g, b] = [c, x, 0];
            } else if (h < 120) {
                [r, g, b] = [x, c, 0];
            } else if (h < 180) {
                [r, g, b] = [0, c, x];
            } else if (h < 240) {
                [r, g, b] = [0, x, c];
            } else if (h < 300) {
                [r, g, b] = [x, 0, c];
            } else {
                [r, g, b] = [c, 0, x];
            }

            const toHex = (v) => Math.round((v + m) * 255).toString(16).padStart(2, '0');

            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        };
    </script>
@endif
