<?php

namespace Tests\Feature;

use Froxlor\Packages\Support\SafeModeRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SafeModeRegistryTest extends TestCase
{
    private const TEST_PACKAGE = 'froxlor/example';

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetRegistry();

        parent::tearDown();
    }

    public function test_disable_and_enable_round_trip(): void
    {
        $registry = app(SafeModeRegistry::class);

        $this->assertFalse($registry->isDisabled(self::TEST_PACKAGE));

        $registry->disable(self::TEST_PACKAGE, 'test failure', auto: true);

        $this->assertTrue($registry->isDisabled(self::TEST_PACKAGE));
        $this->assertSame('test failure', $registry->disabled()[self::TEST_PACKAGE]['reason']);
        $this->assertTrue($registry->disabled()[self::TEST_PACKAGE]['auto']);

        $registry->enable(self::TEST_PACKAGE);

        $this->assertFalse($registry->isDisabled(self::TEST_PACKAGE));
    }

    public function test_core_framework_package_cannot_be_disabled(): void
    {
        $registry = app(SafeModeRegistry::class);

        $registry->disable('froxlor/framework', 'should not be allowed');

        $this->assertFalse($registry->isDisabled('froxlor/framework'));
    }

    public function test_package_not_present_in_composer_lock_cannot_be_disabled(): void
    {
        $registry = app(SafeModeRegistry::class);

        $registry->disable('vendor/not-installed', 'should not be allowed');

        $this->assertFalse($registry->isDisabled('vendor/not-installed'));
    }

    private function resetRegistry(): void
    {
        File::delete(storage_path('framework/froxlor-safe-mode.json'));
    }
}
