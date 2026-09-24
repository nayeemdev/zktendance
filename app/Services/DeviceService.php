<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Employee;
use App\Services\Device\DeviceClient;
use Illuminate\Support\Collection;
use Throwable;

class DeviceService
{
    public function __construct(
        private DeviceClient $client,
        private AttendanceLogService $logs,
    ) {}

    public function testConnection(Device $device): array
    {
        return $this->run($device, fn () => $this->client->info());
    }

    public function pullAttendance(Device $device): int
    {
        $records = $this->run($device, fn () => $this->client->attendance());
        $count = $this->logs->store($device, $records);
        $device->update(['last_synced_at' => now()]);

        return $count;
    }

    public function refreshUsers(Device $device): int
    {
        if ($device->isPush()) {
            $this->queueCommand($device, 'DATA QUERY USERINFO');

            return 0;
        }

        $users = $this->run($device, fn () => $this->client->users());
        $this->storeUsers($device, $users);

        return count($users);
    }

    /**
     * @param  array<int, array{user_id: string, name?: string|null, uid?: int|null}>  $users
     */
    public function storeUsers(Device $device, array $users, bool $replace = true): void
    {
        $users = array_filter($users, fn ($u) => trim((string) $u['user_id']) !== '');

        foreach ($users as $user) {
            $device->users()->updateOrCreate(
                ['user_id' => trim((string) $user['user_id'])],
                ['name' => $user['name'] ?? null, 'uid' => $user['uid'] ?? null]
            );
        }

        if ($replace) {
            $device->users()->whereNotIn('user_id', array_map(fn ($u) => trim((string) $u['user_id']), $users))->delete();
        }
    }

    public function syncTime(Device $device): void
    {
        if ($device->isPush()) {
            $this->queueCommand($device, 'SET OPTION DateTime='.$this->encodeAdmsTime());

            return;
        }

        $this->run($device, fn () => $this->client->setTime(now()->format('Y-m-d H:i:s')));
    }

    public function restart(Device $device): void
    {
        if ($device->isPush()) {
            $this->queueCommand($device, 'REBOOT');

            return;
        }

        $this->run($device, fn () => $this->client->restart());
    }

    public function clearLogs(Device $device): void
    {
        if ($device->isPush()) {
            $this->queueCommand($device, 'CLEAR LOG');

            return;
        }

        $this->run($device, fn () => $this->client->clearAttendance());
    }

    public function pushEmployees(Device $device, ?Collection $employees = null): int
    {
        $employees ??= Employee::active()
            ->where('branch_id', $device->branch_id)
            ->whereNotNull('device_user_id')
            ->get();

        if ($device->isPush()) {
            foreach ($employees as $employee) {
                $this->queueCommand($device, "DATA UPDATE USERINFO PIN={$employee->device_user_id}\tName={$employee->name}\tPri=0");
            }

            return $employees->count();
        }

        return $this->run($device, function () use ($device, $employees) {
            $existing = collect($this->client->users())->keyBy('user_id');
            $nextUid = (int) $existing->max('uid') + 1;
            $count = 0;

            foreach ($employees as $employee) {
                $uid = $existing->get($employee->device_user_id)['uid'] ?? $nextUid++;
                if ($this->client->setUser($uid, $employee->device_user_id, $employee->name)) {
                    $count++;
                }
            }

            $this->storeUsers($device, $this->client->users());

            return $count;
        });
    }

    public function removeEmployee(Device $device, Employee $employee): void
    {
        if (! $employee->device_user_id) {
            return;
        }

        if ($device->isPush()) {
            $this->queueCommand($device, "DATA DELETE USERINFO PIN={$employee->device_user_id}");

            return;
        }

        $this->run($device, function () use ($employee) {
            $user = collect($this->client->users())->firstWhere('user_id', $employee->device_user_id);

            return $user ? $this->client->removeUser($user['uid']) : false;
        });
    }

    public function queueCommand(Device $device, string $command): DeviceCommand
    {
        return $device->commands()->create(['command' => $command]);
    }

    private function run(Device $device, callable $callback): mixed
    {
        if ($device->isPush()) {
            throw new \RuntimeException('This action needs a pull mode device. Push devices send data automatically.');
        }

        try {
            $this->client->connect($device);
            $result = $callback();
            $device->update(['last_seen_at' => now(), 'last_error' => null]);

            return $result;
        } catch (Throwable $e) {
            $device->update(['last_error' => mb_substr($e->getMessage(), 0, 250)]);
            throw $e;
        } finally {
            $this->client->disconnect();
        }
    }

    private function encodeAdmsTime(): int
    {
        $t = now();

        return (($t->year % 100) * 12 * 31 + ($t->month - 1) * 31 + $t->day - 1) * 86400
            + ($t->hour * 60 + $t->minute) * 60 + $t->second;
    }
}
