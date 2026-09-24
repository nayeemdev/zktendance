<?php

namespace Tests\Fakes;

use App\Models\Device;
use App\Services\Device\DeviceClient;

class FakeDeviceClient implements DeviceClient
{
    public array $records = [];

    public array $users = [];

    public bool $fail = false;

    public function connect(Device $device): void
    {
        if ($this->fail) {
            throw new \RuntimeException("Could not connect to {$device->ip_address}:{$device->port}");
        }
    }

    public function disconnect(): void {}

    public function info(): array
    {
        return ['device_name' => 'K40', 'serial_number' => 'FAKE123'];
    }

    public function attendance(): array
    {
        return $this->records;
    }

    public function users(): array
    {
        return $this->users;
    }

    public function setUser(int $uid, string $userId, string $name): bool
    {
        $this->users[] = ['uid' => $uid, 'user_id' => $userId, 'name' => $name];

        return true;
    }

    public function removeUser(int $uid): bool
    {
        return true;
    }

    public function setTime(string $time): bool
    {
        return true;
    }

    public function clearAttendance(): bool
    {
        $this->records = [];

        return true;
    }

    public function restart(): bool
    {
        return true;
    }
}
