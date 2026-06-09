<?php $contentId = $contentId ?? 'accordion-content-' . \Illuminate\Support\Str::slug($name ?? uniqid()); ?>
<div {{ $attributes->twMerge() }} data-accordion-item data-name="{{ $name ?? '' }}" data-collapsible="{{ isset($collapsible) && $collapsible ? '1' : '0' }}" data-content-id="{{ $contentId }}">
    {{ $slot }}
</div>
