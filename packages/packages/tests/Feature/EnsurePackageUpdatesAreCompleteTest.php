<?php

namespace Tests\Feature;

use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Setting;
use Froxlor\Packages\Services\PackageService;
use Tests\TestCase;

class EnsurePackageUpdatesAreCompleteTest extends TestCase
{
    private const TEST_PACKAGE = 'froxlor/example';

    protected function tearDown(): void
    {
        // completeCompletion() below now also runs any migrations that were held back — froxlor/
        // example's add_style_to_example_visits_table migration needs this setting present (see
        // FroxlorExampleServiceProvider), which is unrelated to what these tests exercise, so
        // make sure it's satisfied regardless of test order.
        Setting::set('example.greeting_style', Setting::get('example.greeting_style', 'casual'), 'string', source: self::TEST_PACKAGE);

        app(PackageService::class)->findProvider(self::TEST_PACKAGE)?->completeCompletion();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_login_not_the_pending_route(): void
    {
        app(PackageService::class)->findProvider(self::TEST_PACKAGE)
            ->requireCompletion('finish setting up', 'example.update');

        $this->get('/example')->assertRedirect(route('login'));
    }

    public function test_authenticated_requests_are_redirected_to_the_pending_route(): void
    {
        app(PackageService::class)->findProvider(self::TEST_PACKAGE)
            ->requireCompletion('finish setting up', 'example.update');

        $this->actingAs(User::query()->firstOrFail())
            ->get('/example')
            ->assertRedirect(route('example.update'));
    }

    public function test_escape_hatch_routes_stay_reachable(): void
    {
        app(PackageService::class)->findProvider(self::TEST_PACKAGE)
            ->requireCompletion('finish setting up', 'example.update');

        $this->actingAs(User::query()->firstOrFail())
            ->get(route('packages.index'))
            ->assertOk();
    }

    public function test_completing_the_pending_action_lifts_the_lockout(): void
    {
        $provider = app(PackageService::class)->findProvider(self::TEST_PACKAGE);
        $provider->requireCompletion('finish setting up', 'example.update');

        // completeCompletion() below also runs any migrations that were held back — froxlor/
        // example's add_style_to_example_visits_table migration needs this setting present.
        Setting::set('example.greeting_style', Setting::get('example.greeting_style', 'casual'), 'string', source: self::TEST_PACKAGE);

        $provider->completeCompletion();

        $this->actingAs(User::query()->firstOrFail())
            ->get('/example')
            ->assertOk();
    }
}
