<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ListTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:tenants
                            {--ids : Show tenant ULIDs }
                            {--stats : Show direct user and child counts }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List tenants in a tree structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'subTenants'])
            ->orderBy('name')
            ->get()
            ->groupBy(fn(Tenant $tenant) => $tenant->parent_tenant_id ?? 'root');

        $roots = $tenants->get('root', new Collection());

        if ($roots->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $roots->values()->each(function (Tenant $tenant, int $index) use ($roots, $tenants) {
            $this->renderTenant(
                tenant: $tenant,
                groupedTenants: $tenants,
                prefix: '',
                isLast: $index === $roots->count() - 1,
                isRoot: true,
            );
        });

        return self::SUCCESS;
    }

    /**
     * Render one tenant and its children using tree-style branch prefixes.
     */
    private function renderTenant(Tenant $tenant, Collection $groupedTenants, string $prefix, bool $isLast, bool $isRoot = false): void
    {
        $branch = $isRoot
            ? ''
            : ($isLast ? '`-- ' : '|-- ');

        $this->line($prefix . $branch . $this->formatTenant($tenant));

        $children = $groupedTenants->get($tenant->id, new Collection())->values();
        $childPrefix = $prefix . ($isRoot ? '' : ($isLast ? '    ' : '|   '));

        $children->each(function (Tenant $child, int $index) use ($children, $groupedTenants, $childPrefix) {
            $this->renderTenant(
                tenant: $child,
                groupedTenants: $groupedTenants,
                prefix: $childPrefix,
                isLast: $index === $children->count() - 1,
            );
        });
    }

    /**
     * Format one tenant line for CLI output.
     */
    private function formatTenant(Tenant $tenant): string
    {
        $line = $tenant->name;

        if ($this->option('ids')) {
            $line .= ' (' . $tenant->id . ')';
        }

        if ($this->option('stats')) {
            $line .= ' [' . $tenant->users_count . ' users, ' . $tenant->sub_tenants_count . ' children]';
        }

        return $line;
    }
}
