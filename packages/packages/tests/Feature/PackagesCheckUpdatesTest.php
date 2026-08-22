<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Tenant;
use Froxlor\Packages\Notifications\PackageUpdatesAvailable;
use Froxlor\Packages\Services\PackageService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PackagesCheckUpdatesTest extends TestCase
{
    use DatabaseTransactions;

    private function fakeAvailableUpdates(array $updates): void
    {
        $this->mock(PackageService::class, function ($mock) use ($updates) {
            $mock->shouldReceive('availableUpdates')->andReturn($updates);
        });
    }

    private function updateFixture(string $version = 'v1.1.0'): array
    {
        return [
            [
                'name' => 'froxlor/example',
                'version' => 'v1.0.0',
                'latest_version' => $version,
            ],
        ];
    }

    private function rootTenant(): Tenant
    {
        return Tenant::query()->root()->firstOrFail();
    }

    private function updateNotifications(Tenant $tenant)
    {
        return $tenant->notifications()->where('type', PackageUpdatesAvailable::class);
    }

    public function test_notifies_root_tenant_when_updates_are_available(): void
    {
        $this->fakeAvailableUpdates($this->updateFixture());

        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $tenant = $this->rootTenant();
        $notification = $this->updateNotifications($tenant)->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(['froxlor/example v1.1.0'], $notification->data['packages']);
        $this->assertNotEmpty($notification->data['title']);
        $this->assertNotEmpty($notification->data['message']);
    }

    public function test_does_not_duplicate_notification_for_unchanged_updates(): void
    {
        $this->fakeAvailableUpdates($this->updateFixture());

        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();
        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $this->assertSame(1, $this->updateNotifications($this->rootTenant())->count());
    }

    public function test_does_not_renotify_after_notification_was_read(): void
    {
        $this->fakeAvailableUpdates($this->updateFixture());

        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $tenant = $this->rootTenant();
        $this->updateNotifications($tenant)->first()->markAsRead();

        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $this->assertSame(1, $this->updateNotifications($tenant)->count());
        $this->assertSame(0, $tenant->unreadNotifications()->where('type', PackageUpdatesAvailable::class)->count());
    }

    public function test_replaces_unread_notification_when_updates_change(): void
    {
        $this->fakeAvailableUpdates($this->updateFixture('v1.1.0'));
        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $this->fakeAvailableUpdates($this->updateFixture('v1.2.0'));
        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $tenant = $this->rootTenant();

        $this->assertSame(1, $this->updateNotifications($tenant)->count());
        $this->assertSame(['froxlor/example v1.2.0'], $this->updateNotifications($tenant)->first()->data['packages']);
    }

    public function test_removes_unread_notification_when_no_updates_remain(): void
    {
        $this->fakeAvailableUpdates($this->updateFixture());
        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $this->fakeAvailableUpdates([]);
        $this->artisan('froxlor:packages:check-updates')->assertSuccessful();

        $this->assertSame(0, $this->updateNotifications($this->rootTenant())->count());
    }
}
