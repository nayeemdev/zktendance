<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Console\Command;
use Throwable;

class SyncDevices extends Command
{
    protected $signature = 'zk:sync {device? : Device ID, all active pull devices when empty}';

    protected $description = 'Download attendance logs from ZKTeco devices in pull mode';

    public function handle(DeviceService $service): int
    {
        $devices = Device::where('is_active', true)
            ->where('connection_mode', Device::MODE_PULL)
            ->when($this->argument('device'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        foreach ($devices as $device) {
            try {
                $count = $service->pullAttendance($device);
                $this->info("{$device->name}: {$count} new punches");
            } catch (Throwable $e) {
                $this->error("{$device->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
