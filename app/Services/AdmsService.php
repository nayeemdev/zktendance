<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Cache;

class AdmsService
{
    public function __construct(private AttendanceLogService $logs) {}

    public function findDevice(?string $serial): ?Device
    {
        if (! $serial) {
            return null;
        }

        $device = Device::where('serial_number', $serial)->where('connection_mode', Device::MODE_PUSH)->first();

        if (! $device) {
            $unknown = Cache::get('adms_unknown_devices', []);
            $unknown[$serial] = ['serial' => $serial, 'ip' => request()->ip(), 'seen_at' => now()->toDateTimeString()];
            Cache::put('adms_unknown_devices', $unknown, now()->addDays(7));

            return null;
        }

        $device->update(['last_seen_at' => now(), 'ip_address' => request()->ip()]);

        return $device->is_active ? $device : null;
    }

    public function handshake(Device $device): string
    {
        return implode("\n", [
            "GET OPTION FROM: {$device->serial_number}",
            'ATTLOGStamp=None',
            'OPERLOGStamp=9999',
            'ATTPHOTOStamp=None',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog',
            'TimeZone='.(int) round(now()->utcOffset() / 60),
            'Realtime=1',
            'Encrypt=None',
        ]);
    }

    public function receiveAttendance(Device $device, string $body): int
    {
        $records = [];

        foreach (preg_split('/\r\n|\n|\r/', trim($body)) as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) < 2 || ! strtotime($parts[1])) {
                continue;
            }

            $records[] = [
                'user_id' => $parts[0],
                'timestamp' => $parts[1],
                'state' => $parts[2] ?? null,
                'type' => $parts[3] ?? null,
            ];
        }

        $this->logs->store($device, $records);
        $device->update(['last_synced_at' => now()]);

        return count($records);
    }

    public function pendingCommands(Device $device): string
    {
        $commands = $device->commands()->where('status', 'pending')->orderBy('id')->limit(20)->get();

        if ($commands->isEmpty()) {
            return 'OK';
        }

        DeviceCommand::whereIn('id', $commands->pluck('id'))->update(['status' => 'sent', 'sent_at' => now()]);

        return $commands->map(fn ($c) => "C:{$c->id}:{$c->command}")->implode("\n");
    }

    public function commandResult(Device $device, string $body): void
    {
        foreach (preg_split('/\r\n|\n|\r/', trim($body)) as $line) {
            parse_str(trim($line), $result);
            if (empty($result['ID'])) {
                continue;
            }

            $device->commands()->where('id', $result['ID'])->update([
                'status' => ($result['Return'] ?? '-1') === '0' ? 'done' : 'failed',
                'response' => trim($line),
            ]);
        }
    }
}
