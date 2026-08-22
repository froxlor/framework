<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Setting as SettingModel;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Core\Support\Setting;
use Froxlor\Packages\Services\PackageService;
use Tests\TestCase;

class PendingCompletionTest extends TestCase
{
    private const TEST_PACKAGE = 'froxlor/example';

    protected function tearDown(): void
    {
        // completeCompletion() below now also runs any migrations that were held back — froxlor/
        // example's add_style_to_example_visits_table migration needs this setting present (see
        // FroxlorExampleServiceProvider), which is unrelated to what these tests exercise, so
        // make sure it's satisfied regardless of test order.
        Setting::set('example.greeting_style', Setting::get('example.greeting_style', 'casual'), 'string');

        app(PackageService::class)->findProvider(self::TEST_PACKAGE)?->completeCompletion();

        SettingModel::query()
            ->where('category', 'packages')
            ->where('key', 'test/fake-package.pending')
            ->delete();

        parent::tearDown();
    }

    public function test_require_and_complete_completion_round_trip(): void
    {
        $provider = new class (app()) extends PackageServiceProvider {
            public function packageName(): string
            {
                return 'test/fake-package';
            }
        };

        $this->assertFalse($provider->hasPendingCompletion());

        $provider->requireCompletion('needs an answer', 'some.route');

        $this->assertTrue($provider->hasPendingCompletion());
        $this->assertSame(
            ['reason' => 'needs an answer', 'route' => 'some.route', 'stage' => 'updating'],
            $provider->pendingCompletion()
        );

        $provider->completeCompletion();

        $this->assertFalse($provider->hasPendingCompletion());
    }

    public function test_pending_completions_lists_enabled_toggleable_packages_only(): void
    {
        $packageService = app(PackageService::class);
        $provider = $packageService->findProvider(self::TEST_PACKAGE);

        $this->assertSame([], $packageService->pendingCompletions());

        $provider->requireCompletion('finish setting up', 'example.update');

        $pending = $packageService->pendingCompletions();

        $this->assertArrayHasKey(self::TEST_PACKAGE, $pending);
        $this->assertSame('finish setting up', $pending[self::TEST_PACKAGE]['reason']);
        $this->assertSame('example.update', $pending[self::TEST_PACKAGE]['route']);
    }

    public function test_disabled_package_is_excluded_from_pending_completions(): void
    {
        $packageService = app(PackageService::class);
        $provider = $packageService->findProvider(self::TEST_PACKAGE);

        $provider->requireCompletion('finish setting up', 'example.update');
        $packageService->disablePackage(self::TEST_PACKAGE);

        try {
            $this->assertSame([], $packageService->pendingCompletions());
        } finally {
            $packageService->enablePackage(self::TEST_PACKAGE);
        }
    }
}
