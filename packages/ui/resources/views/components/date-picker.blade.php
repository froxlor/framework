@props([
    'name' => null,
    'value' => null,
    'placeholder' => 'YYYY-MM-DD',
    'displayFormat' => 'YYYY-MM-DD',
    'modelFormat' => 'YYYY-MM-DD',
    'min' => null,
    'max' => null,
    'disabled' => false,
    'required' => false,
    'weekStart' => 1,
])

@php
    $disabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
    $required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
@endphp

<div
    x-data="datePicker({
        value: @js($value),
        displayFormat: @js($displayFormat),
        modelFormat: @js($modelFormat),
        min: @js($min),
        max: @js($max),
        weekStart: @js((int)$weekStart),
        disabled: @js($disabled),
    })"
    x-on:keydown.escape.window="open=false"
    class="relative inline-flex w-full"
>
    <input type="hidden" name="{{ $name }}" x-model="valueModel" :disabled="disabled">

    <div class="relative flex-1">
        <input type="text"
               :disabled="disabled"
               x-model="valueDisplay"
               x-on:click="if (!disabled) toggle()"
               x-on:keydown.enter.prevent.stop="if (!disabled) toggle()"
               x-on:keydown.space.prevent.stop="if (!disabled) toggle()"
               placeholder="{{ $placeholder }}"
               readonly
               class="w-full dark:bg-zinc-700 px-4 py-2 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm pr-10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        />
        <button type="button"
                x-on:click="if (!disabled) toggle()"
                :disabled="disabled"
                :class="disabled ? 'pointer-events-none opacity-50' : ''"
                class="absolute inset-y-0 right-0 px-3 inline-flex items-center text-zinc-500 focus:outline-none"
                aria-label="Open calendar"
        >
            <x-ui::icon name="calendar" />
        </button>
    </div>

    <!-- Calendar Panel -->
    <div x-cloak x-show="open && !disabled"
         x-transition.opacity.scale.100
         x-on:click.outside="open=false"
         class="absolute z-50 mt-2 top-full left-0 w-80 rounded-md border border-zinc-800 bg-zinc-900 text-white shadow-xl p-3"
    >
        <div class="flex items-center justify-between mb-2">
            <button type="button" class="p-1 rounded hover:bg-zinc-800"
                    x-on:click="if (!disabled) prevMonth()" aria-label="Previous month">
                <x-ui::icon name="chevron-left" />
            </button>
            <div class="font-medium"><span x-text="monthLabel()"></span></div>
            <button type="button" class="p-1 rounded hover:bg-zinc-800"
                    x-on:click="if (!disabled) nextMonth()" aria-label="Next month">
                <x-ui::icon name="chevron-right" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs text-zinc-400 select-none mb-1">
            <template x-for="d in weekdayLabels" :key="d"><div x-text="d"></div></template>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <template x-for="n in leadingBlanks()" :key="'b'+n"><div></div></template>
            <template x-for="day in daysArray()" :key="day">
                <button type="button"
                        class="h-9 w-9 rounded-md text-sm"
                        :class="dayClasses(day)"
                        x-on:click="if (!disabled) pick(day)"
                        x-text="day"
                        :disabled="!isSelectable(day) || disabled"
                ></button>
            </template>
        </div>

        <div class="flex items-center justify-between mt-3 text-xs">
            <button type="button" class="px-2 py-1 rounded hover:bg-zinc-800"
                    x-on:click="if (!disabled) today()">Today</button>
            <button type="button" class="px-2 py-1 text-zinc-400 hover:text-white"
                    x-on:click="if (!disabled) clear()">Clear</button>
        </div>
    </div>

    <script>
        if (!window.__uiDatePickerRegistered) {
            document.addEventListener('alpine:init', () => {
                Alpine.data('datePicker', (opts) => ({
                    open: false,
                    disabled: opts.disabled ?? false,
                    valueModel: '',
                    valueDisplay: '',
                    viewYear: 0,
                    viewMonth: 0,
                    weekStart: opts.weekStart ?? 1,
                    min: opts.min ? new Date(opts.min) : null,
                    max: opts.max ? new Date(opts.max) : null,
                    displayFormat: opts.displayFormat || 'YYYY-MM-DD',
                    modelFormat: opts.modelFormat || 'YYYY-MM-DD',
                    weekdayLabels: [],

                    init() {
                        const today = new Date();
                        const parsed = this.parse(opts.value, this.modelFormat);
                        const base = parsed || today;

                        this.viewYear = base.getFullYear();
                        this.viewMonth = base.getMonth();
                        this.valueModel = parsed ? this.format(parsed, this.modelFormat) : '';
                        this.valueDisplay = parsed ? this.format(parsed, this.displayFormat) : '';
                        this.weekdayLabels = this.weekdayOrder(['Su','Mo','Tu','We','Th','Fr','Sa']);
                    },

                    toggle() { if (!this.disabled) this.open = !this.open; },
                    prevMonth() { if (this.disabled) return; if (--this.viewMonth < 0) { this.viewMonth = 11; this.viewYear--; } },
                    nextMonth() { if (this.disabled) return; if (++this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; } },

                    daysInMonth() { return new Date(this.viewYear, this.viewMonth + 1, 0).getDate(); },
                    daysArray() { return Array.from({ length: this.daysInMonth() }, (_, i) => i + 1); },
                    firstDayOfMonth() { return new Date(this.viewYear, this.viewMonth, 1).getDay(); },
                    leadingBlanks() {
                        const f = this.firstDayOfMonth();
                        const shift = (f - this.weekStart + 7) % 7;
                        return Array.from({ length: shift }, (_, i) => i + 1);
                    },

                    isSelectable(day) {
                        const d = new Date(this.viewYear, this.viewMonth, day);
                        return !(
                            (this.min && d < this.stripTime(this.min)) ||
                            (this.max && d > this.stripTime(this.max))
                        );
                    },
                    dayClasses(day) {
                        const d = new Date(this.viewYear, this.viewMonth, day);
                        const today = this.stripTime(new Date());
                        const isToday = +this.stripTime(d) === +today;
                        const valDate = this.valueModel ? this.parse(this.valueModel, this.modelFormat) : null;
                        const isSelected = valDate && (+this.stripTime(d) === +this.stripTime(valDate));
                        const disabled = this.disabled || !this.isSelectable(day);

                        return [
                            'hover:bg-zinc-800',
                            isToday ? 'ring-1 ring-primary/60' : '',
                            isSelected ? 'bg-primary text-white hover:bg-primary/90' : '',
                            disabled ? 'opacity-40 cursor-not-allowed hover:bg-transparent' : '',
                        ].filter(Boolean).join(' ');
                    },

                    pick(day) {
                        if (this.disabled || !this.isSelectable(day)) return;
                        const d = new Date(this.viewYear, this.viewMonth, day);
                        this.valueModel = this.format(d, this.modelFormat);
                        this.valueDisplay = this.format(d, this.displayFormat);
                        this.open = false;
                    },
                    today() {
                        if (this.disabled) return;
                        const t = new Date();
                        this.viewYear = t.getFullYear();
                        this.viewMonth = t.getMonth();
                        this.pick(t.getDate());
                    },
                    clear() {
                        if (this.disabled) return;
                        this.valueModel = '';
                        this.valueDisplay = '';
                        this.open = false;
                    },

                    monthLabel() {
                        const m = ['January','February','March','April','May','June','July','August','September','October','November','December'][this.viewMonth];
                        return `${m} ${this.viewYear}`;
                    },

                    weekdayOrder(labels) { return labels.slice(this.weekStart).concat(labels.slice(0, this.weekStart)); },
                    stripTime(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); },
                    pad(n) { return n < 10 ? '0' + n : '' + n; },
                    format(d, fmt) {
                        if (!(d instanceof Date)) return '';
                        const YYYY = d.getFullYear();
                        const MM = this.pad(d.getMonth() + 1);
                        const DD = this.pad(d.getDate());
                        return fmt.replace('YYYY', YYYY).replace('MM', MM).replace('DD', DD);
                    },
                    parse(str, fmt) {
                        if (!str) return null;
                        try {
                            if (fmt === 'YYYY-MM-DD') {
                                const [y, m, d] = str.split('-').map(Number);
                                return new Date(y, (m || 1) - 1, d || 1);
                            }
                            if (fmt === 'DD.MM.YYYY') {
                                const [d, m, y] = str.split('.').map(Number);
                                return new Date(y || 1970, (m || 1) - 1, d || 1);
                            }
                            if (fmt === 'MM/DD/YYYY') {
                                const [m, d, y] = str.split('/').map(Number);
                                return new Date(y || 1970, (m || 1) - 1, d || 1);
                            }
                            const dt = new Date(str);
                            return isNaN(+dt) ? null : dt;
                        } catch (e) { return null; }
                    },
                }))
            });
            window.__uiDatePickerRegistered = true;
        }
    </script>
</div>
