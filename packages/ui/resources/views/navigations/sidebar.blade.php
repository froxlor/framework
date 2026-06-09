<?php

namespace Froxlor\UI\Livewire;

use Froxlor\Core\Models\User;
use Froxlor\UI\Support\UI;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Component;

new class extends Component {
    public Authenticatable|User|null $user;
    public ?object $currentTenant = null;
    public string|int|null $currentTenantId = null;

    public function __construct(
        public bool $autoHide = false,
        public ?string $navigation = null,
        public ?string $navigationFooter = null,
        public bool $tenantNavigation = false,
        public bool $collapsible = false,
        public ?string $class = null
    )
    {
        $this->user = auth()->user();

        if ($this->user && method_exists($this->user, 'tenants')) {
            $this->user->loadMissing('tenants');
        }

        $tenant = request()->route('tenant') ?? request()->query('tenant');
        $this->currentTenantId = $tenant instanceof \Froxlor\Core\Models\Tenant ? $tenant->id : $tenant;
        $this->currentTenant = $this->currentTenantId
            ? $this->user?->tenants?->firstWhere('id', $this->currentTenantId)
            : null;
    }

    public function items(?string $stack): array
    {
        if (!$stack) {
            return [];
        }

        return UI::stack($stack);
    }

    public function hasVisible(array $items): bool
    {
        return count(array_filter($items, fn ($item) => !empty($item->visible))) > 0;
    }

    public function hasTenantContext(): bool
    {
        return request()->routeIs('tenants.*') || request()->query('nav') === 'tenant';
    }

};
?>

@php
    $navigationItems = $this->items($navigation);
    $footerItems = $this->items($navigationFooter);
    $tenantItems = $this->items('tenant-sidebar');
@endphp

<div
    class="flex"
    x-data="{
        collapsed: false,
        ready: false,
        storageKey: 'ui-sidebar:' + @js($navigation ?? 'default') + ':collapsed',
        init() {
            this.collapsed = localStorage.getItem(this.storageKey) === 'true';

            this.$watch('collapsed', (value) => {
                localStorage.setItem(this.storageKey, value ? 'true' : 'false');
            });

            requestAnimationFrame(() => {
                this.ready = true;
            });
        }
    }"
>
    @if($this->hasVisible($navigationItems))
        <x-ui::sidebar
            :name="$navigation ?? 'sidebar'"
            {{ $attributes->twMerge($class) }}
           x-bind:style="collapsed && desktop ? 'width: 5rem' : ''"
           x-bind:class="ready ? 'transition-[width] duration-300' : ''"
           class="overflow-visible lg:overflow-visible"
        >
            @if($tenantNavigation && $user && $user->tenants?->isNotEmpty())
                @include('ui::navigations.sidebar.tenant-selector')
            @endif

            <x-ui::sidebar.content>
                <x-ui::sidebar.group>
                    <x-ui::sidebar.group-content>
                        @include('ui::navigations.sidebar.nav-items', ['items' => $navigationItems])
                    </x-ui::sidebar.group-content>
                </x-ui::sidebar.group>

                @if($tenantNavigation && $user && $user->tenants?->isNotEmpty() && $this->currentTenantId && $this->hasTenantContext() && $this->hasVisible($tenantItems))
                    <x-ui::sidebar.group>
                        @include('ui::navigations.sidebar.tenant', ['items' => $tenantItems])
                    </x-ui::sidebar.group>
                @endif
            </x-ui::sidebar.content>

            @if($this->hasVisible($footerItems) || $collapsible)
                <x-ui::sidebar.footer>
                    @include('ui::navigations.sidebar.footer', ['items' => $footerItems, 'collapsible' => $collapsible])
                </x-ui::sidebar.footer>
            @endif
        </x-ui::sidebar>
    @endif
</div>
