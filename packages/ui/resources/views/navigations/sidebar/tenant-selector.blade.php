<div class="shrink-0 pb-4">
    <x-ui::sidebar.group>
        <x-ui::sidebar.group-content
            x-bind:class="collapsed && desktop ? 'bg-transparent px-2 pt-0 pb-0' : 'rounded-md bg-black/40 px-4 pt-4 pb-2'"
            class="list-none"
        >
            <div x-bind:class="collapsed && desktop ? 'flex w-full justify-center' : ''">
                <x-ui::dropdown align="left" width="w-[min(24rem,calc(100vw-2rem))]" content-classes="bg-zinc-950 py-2" :close-on-content-click="false">
                    <x-slot:trigger>
                        <div class="flex w-full items-center gap-3" :class="collapsed && desktop ? 'min-h-10 justify-center rounded-md px-2' : ''">
                            <x-ui::avatar variant="square" class="shrink-0" x-bind:class="collapsed && desktop ? 'size-12' : 'size-8'">
                                <x-ui::avatar.fallback variant="square">{{ $currentTenant?->initials ?? 'T' }}</x-ui::avatar.fallback>
                            </x-ui::avatar>
                            <div class="min-w-0" x-cloak x-show="!collapsed || !desktop">
                                <div class="truncate text-sm font-medium text-zinc-100">
                                    {{ $currentTenant?->name ?? 'Select tenant' }}
                                </div>
                                <div class="truncate text-xs text-zinc-500">
                                    {{ $currentTenant?->description ?? 'Choose a tenant context' }}
                                </div>
                            </div>
                            <x-ui::icon name="chevron-down" class="ml-auto text-zinc-400" x-cloak x-show="!collapsed || !desktop"/>
                        </div>
                    </x-slot:trigger>
                    <x-slot:content>
                        <div
                            x-data="{
                                search: '',
                                matches(term) {
                                    return term.includes(this.search.toLowerCase());
                                },
                                hasResults() {
                                    return Array.from(this.$refs.tenantList.children).some((element) => element.dataset.tenantVisible === 'true' && element.style.display !== 'none');
                                }
                            }"
                            x-init="$watch('open', (value) => { if (value) { $nextTick(() => $refs.tenantSearch?.focus()) } })"
                            class="space-y-2"
                        >
                            <div class="sticky top-0 z-10 space-y-2 bg-zinc-950 px-2 pb-2">
                                <x-ui::input
                                    x-ref="tenantSearch"
                                    x-model="search"
                                    type="search"
                                    placeholder="Search tenants..."
                                    class="border-zinc-700 bg-zinc-900 text-zinc-100 placeholder:text-zinc-500 focus:border-primary"
                                />
                            </div>

                            <div x-ref="tenantList" class="max-h-80 space-y-1 overflow-y-auto px-2">
                                @foreach($user->tenants as $tenant)
                                    <div
                                        data-tenant-visible="true"
                                        x-show="matches(@js(strtolower($tenant->name . ' ' . ($tenant->description ?? '') . ' ' . $tenant->initials)))"
                                        class="w-full"
                                    >
                                        <x-ui::dropdown.link
                                            wire:navigate
                                            href="{{ route('tenants.show', ['tenant' => $tenant->id]) }}"
                                            @class([
                                                'w-full rounded-md text-zinc-100 hover:text-zinc-100',
                                                'bg-primary/15 hover:bg-primary/20' => (string) $tenant->id === (string) $currentTenantId,
                                                'hover:rounded-md hover:bg-zinc-800' => (string) $tenant->id !== (string) $currentTenantId,
                                            ])
                                        >
                                            <div class="flex w-full items-center gap-3">
                                                <x-ui::avatar variant="square" class="size-8">
                                                    <x-ui::avatar.fallback variant="square">{{ $tenant->initials }}</x-ui::avatar.fallback>
                                                </x-ui::avatar>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <div class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-100">
                                                            {{ $tenant->name }}
                                                        </div>
                                                        @if((string) $tenant->id === (string) $currentTenantId)
                                                            <x-ui::badge size="sm" variant="secondary" class="ml-auto shrink-0">Current</x-ui::badge>
                                                        @endif
                                                    </div>
                                                    <div class="truncate text-xs text-zinc-500">
                                                        {{ $tenant->description ?? 'No description' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </x-ui::dropdown.link>
                                    </div>
                                @endforeach

                                <div
                                    x-show="!hasResults()"
                                    class="px-4 py-6 text-center text-sm text-zinc-500"
                                >
                                    No tenants found.
                                </div>
                            </div>
                        </div>
                    </x-slot:content>
                </x-ui::dropdown>
            </div>
        </x-ui::sidebar.group-content>
    </x-ui::sidebar.group>
</div>
