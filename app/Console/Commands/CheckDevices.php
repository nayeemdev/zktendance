<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckDevices extends Command
{
    protected $signature = 'devices:check';

    protected $description = 'Alert admins about devices that stopped responding';

    public function handle(NotificationService $notifications): int
    {
        $limit = now()->subMinutes((int) setting('device_offline_minutes', 30));

        $devices = Device::with('branch')->where('is_active', true)->get();

        foreach ($devices as $device) {
            $lastContact = $device->last_seen_at ?? $device->created_at;
            $offline = $lastContact->lt($limit);

            if ($offline && ! $device->offline_notified_at) {
                $notifications->staff(
                    'Device offline',
                    "{$device->name} ({$device->branch->name}) has not responded since {$lastContact->format('d M Y h:i A')}.",
                    route('admin.devices.show', $device),
                    ['admin']
                );
                $device->update(['offline_notified_at' => now()]);
                $this->warn("{$device->name} is offline");
            }

            if (! $offline && $device->offline_notified_at) {
                $device->update(['offline_notified_at' => null]);
            }
        }

        return self::SUCCESS;
    }
}
