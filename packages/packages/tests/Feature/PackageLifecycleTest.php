<?php

namespace Tests\Feature;

use Exception;
use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Core\Support\Setting;
use Froxlor\Packages\Services\PackageService;
use Tests\TestCase;

class PackageLifecycleTest extends TestCase
{
    private const TEST_PACKAGE = 'froxlor/example';

    protected function tearDown(): void
    {
        app(PackageService::class)->enablePackage(self::TEST_PACKAGE);

        SettingModel::query()
            ->where('category', 'packages')
            ->whereIn('key', ['test/fake-package.enabled', 'test/fake-package.pending'])
            ->delete();

        parent::tearDown();
    }

    public function test_is_toggleable_excludes_the_bundled_framework_package(): void
    {
        $packageService = app(PackageService::class);

        $this->assertFalse($packageService->isToggleable('froxlor/framework'));
        $this->assertTrue($packageService->isToggleable(self::TEST_PACKAGE));
    }

    public function test_find_provider_resolves_the_example_package(): void
    {
        $provider = app(PackageService::class)->findProvider(self::TEST_PACKAGE);

        $this->assertNotNull($provider);
        $this->assertSame(self::TEST_PACKAGE, $provider->packageName());
    }

    public function test_disabling_and_enabling_a_package_toggles_its_setting(): void
    {
        $packageService = app(PackageService::class);

        $this->assertTrue($packageService->findProvider(self::TEST_PACKAGE)->isEnabled());

        $packageService->disablePackage(self::TEST_PACKAGE);

        $this->assertFalse(Setting::get('packages.' . self::TEST_PACKAGE . '.enabled'));
        $this->assertFalse($packageService->findProvider(self::TEST_PACKAGE)->isEnabled());

        $packageService->enablePackage(self::TEST_PACKAGE);

        $this->assertTrue(Setting::get('packages.' . self::TEST_PACKAGE . '.enabled'));
    }

    public function test_the_bundled_framework_package_cannot_be_toggled(): void
    {
        $this->expectException(Exception::class);

        app(PackageService::class)->disablePackage('froxlor/framework');
    }

    public function test_enable_and_disable_fire_lifecycle_hooks_in_order(): void
    {
        $provider = new class (app()) extends PackageServiceProvider {
            public array $calls = [];

            public function packageName(): string
            {
                return 'test/fake-package';
            }

            public function enabling(): void
            {
                $this->calls[] = 'enabling';
            }

            public function enabled(): void
            {
                $this->calls[] = 'enabled';
            }

            public function disabling(): void
            {
                $this->calls[] = 'disabling';
            }

            public function disabled(): void
            {
                $this->calls[] = 'disabled';
            }
        };

        $provider->disable();
        $this->assertSame(['disabling', 'disabled'], $provider->calls);

        // Already disabled — calling disable() again must be a no-op.
        $provider->disable();
        $this->assertSame(['disabling', 'disabled'], $provider->calls);

        $provider->enable();
        $this->assertSame(['disabling', 'disabled', 'enabling', 'enabled'], $provider->calls);
    }

    public function test_completing_a_pending_installing_completion_fires_installed_not_updated(): void
    {
        $provider = new class (app()) extends PackageServiceProvider {
            public array $calls = [];

            public function packageName(): string
            {
                return 'test/fake-package';
            }

            public function installed(): void
            {
                $this->calls[] = 'installed';
            }

            public function updated(): void
            {
                $this->calls[] = 'updated';
            }
        };

        $provider->requireCompletion('needs setup', 'packages.index', stage: 'installing');
        $provider->completeCompletion();

        $this->assertSame(['installed'], $provider->calls);
    }

    public function test_completing_a_pending_updating_completion_fires_updated_not_installed(): void
    {
        $provider = new class (app()) extends PackageServiceProvider {
            public array $calls = [];

            public function packageName(): string
            {
                return 'test/fake-package';
            }

            public function installed(): void
            {
                $this->calls[] = 'installed';
            }

            public function updated(): void
            {
                $this->calls[] = 'updated';
            }
        };

        $provider->requireCompletion('needs setup', 'packages.index', stage: 'updating');
        $provider->completeCompletion();

        $this->assertSame(['updated'], $provider->calls);
    }

    public function test_completing_when_nothing_was_pending_fires_no_hooks(): void
    {
        $provider = new class (app()) extends PackageServiceProvider {
            public array $calls = [];

            public function packageName(): string
            {
                return 'test/fake-package';
            }

            public function installed(): void
            {
                $this->calls[] = 'installed';
            }

            public function updated(): void
            {
                $this->calls[] = 'updated';
            }
        };

        $provider->completeCompletion();

        $this->assertSame([], $provider->calls);
    }
}
