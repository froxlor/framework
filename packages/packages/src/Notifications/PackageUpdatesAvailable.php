<?php

namespace Froxlor\Packages\Notifications;

use Illuminate\Notifications\Notification;

class PackageUpdatesAvailable extends Notification
{
    /**
     * @param array<int, array<string, mixed>> $updates entries from PackageService::availableUpdates()
     */
    public function __construct(private readonly array $updates)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $packages = self::describeUpdates($this->updates);

        return [
            'title' => trans('froxlor-packages::generic.update_available'),
            'message' => trans_choice('froxlor-packages::generic.updates_available_notification', count($packages), [
                'packages' => implode(', ', $packages),
            ]),
            'href' => route('packages.updater.index'),
            'icon' => 'package-2',
            'packages' => $packages,
        ];
    }

    /**
     * Normalize the available updates into "name latest_version" strings, used both for the
     * notification payload and to detect whether an equal notification was already sent.
     *
     * @param array<int, array<string, mixed>> $updates
     * @return array<int, string>
     */
    public static function describeUpdates(array $updates): array
    {
        $packages = array_map(
            fn (array $update) => $update['name'] . ' ' . $update['latest_version'],
            $updates
        );

        sort($packages);

        return array_values($packages);
    }
}
