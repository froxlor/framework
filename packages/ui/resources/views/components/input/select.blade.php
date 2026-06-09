@props(['disabled' => false, 'required' => false, 'options' => [], 'value' => null])

<select
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $attributes->twMerge('w-full rounded-lg border border-zinc-200/70 bg-white/90 px-3 py-2 text-zinc-900 shadow-sm outline-none transition-colors focus:border-primary/50 focus:ring-2 focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-100 dark:focus:border-primary/60 dark:focus:ring-primary/20') }}
>
    @foreach($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" {{ (string) $optionValue === (string) $value ? 'selected' : '' }}>{{ $optionLabel }}</option>
    @endforeach
</select>
