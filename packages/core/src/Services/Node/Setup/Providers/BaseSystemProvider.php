<?php

namespace Froxlor\Core\Services\Node\Setup\Providers;

use Froxlor\Core\Services\Node\Setup\NodeServiceContext;
use Froxlor\Core\Services\Node\Setup\NodeServiceProvider;
use Froxlor\Core\Services\Node\Setup\ServicePlan;

/** Minimal OS prerequisites only. Hosting services and environment jails live elsewhere. */
final class BaseSystemProvider implements NodeServiceProvider
{
    public function key(): string
    {
        return 'froxlor/core:base-system';
    }

    public function role(): string
    {
        return 'base-system';
    }

    public function package(): string
    {
        return 'froxlor/core';
    }

    public function revision(): string
    {
        return '1';
    }

    public function platforms(): array
    {
        return ['debian@12', 'debian@13', 'ubuntu@24.04'];
    }

    public function requires(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return [];
    }

    public function settings(): array
    {
        return [];
    }

    public function plan(NodeServiceContext $context): ServicePlan
    {
        return ServicePlan::make()->ensurePackages([
            'ca-certificates', 'logrotate', 'sudo',
            'curl', 'dnsutils', 'iproute2', 'iputils-ping',
            'procps', 'lsof', 'less', 'jq',
        ]);
    }
}
