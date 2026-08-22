<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeNotification(array $data = ['title' => 'Test notification']): Notification
    {
        return new class($data) extends Notification {
            public function __construct(private readonly array $data)
            {
            }

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return $this->data;
            }
        };
    }

    public function test_tenant_can_receive_database_notifications(): void
    {
        $tenant = Tenant::query()->root()->firstOrFail();

        $tenant->notify($this->makeNotification());

        $this->assertSame(1, $tenant->notifications()->count());
        $this->assertSame('Test notification', $tenant->notifications()->first()->data['title']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'tenants',
            'notifiable_id' => $tenant->id,
        ]);
    }

    public function test_user_sees_own_and_member_tenant_notifications(): void
    {
        $rootTenant = Tenant::query()->root()->firstOrFail();
        $rootUser = $rootTenant->users()->firstOrFail();

        $rootTenant->notify($this->makeNotification(['title' => 'Tenant notification']));
        $rootUser->notify($this->makeNotification(['title' => 'User notification']));

        $titles = $rootUser->relevantNotifications()->get()->map(fn ($notification) => $notification->data['title']);

        $this->assertCount(2, $titles);
        $this->assertContains('Tenant notification', $titles);
        $this->assertContains('User notification', $titles);
    }

    public function test_user_does_not_see_notifications_of_foreign_tenants(): void
    {
        $rootTenant = Tenant::query()->root()->firstOrFail();
        $rootTenant->notify($this->makeNotification(['title' => 'Root tenant notification']));

        // dev3 is only a member of the nested subtenant, not of the root tenant
        $foreignUser = User::query()->where('email', 'dev3@froxlor.org')->firstOrFail();

        $this->assertSame(0, $foreignUser->relevantNotifications()->count());
    }
}
