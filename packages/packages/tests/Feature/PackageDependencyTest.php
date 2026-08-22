<?php

namespace Tests\Feature;

use Froxlor\Packages\Services\PackageService;
use Tests\TestCase;

class PackageDependencyTest extends TestCase
{
    public function test_find_dependant_resolves_packages_that_require_a_replaced_name(): void
    {
        // froxlor/framework replaces froxlor/core, froxlor/packages, froxlor/ui — packages
        // requiring those names in composer.json depend on froxlor/framework in composer.lock.
        $dependants = app(PackageService::class)->findDependant('froxlor/framework');

        $this->assertArrayHasKey('froxlor/example', $dependants);
        $this->assertArrayHasKey('froxlor/adapter-remote', $dependants);
    }

    public function test_find_dependant_returns_empty_for_a_leaf_package(): void
    {
        $dependants = app(PackageService::class)->findDependant('froxlor/example');

        $this->assertSame([], $dependants);
    }

    public function test_remove_package_is_blocked_when_dependants_exist(): void
    {
        $response = app(PackageService::class)->removePackage('froxlor/framework');

        $this->assertSame('error', $response['status']);
        $this->assertStringContainsString('froxlor/example', $response['message']);
        $this->assertStringContainsString('froxlor/adapter-remote', $response['message']);
    }
}
