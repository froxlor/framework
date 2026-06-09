<div {{ $attributes->twMerge(['flex flex-col flex-1 sm:justify-center items-center sm:pt-0']) }}>
    @isset($before)
        <div class="flex justify-center w-full p-4 sm:p-12 space-y-4 sm:space-y-12 sm:max-w-md mt-4 sm:mt-0">
            {{ $before }}
        </div>
    @endisset
    <div class="w-full sm:max-w-md overflow-hidden p-4 sm:p-12 space-y-4 sm:space-y-12">
        {{ $slot }}
    </div>
</div>
