<?php

namespace App\Services\Device;

use App\Models\Device;

interface DeviceClient
{
    public function connect(Device $device): void;

    public function disconnect(): void;

    public function info(): array;

    /** @return array<int, array{user_id: string, timestamp: string, state: int, type: int}> */
    public function attendance(): array;

    /** @return array<int, array{uid: int, user_id: string, name: string}> */
    public function users(): array;

    public function setUser(int $uid, string $userId, string $name): bool;

    public function removeUser(int $uid): bool;

    public function setTime(string $time): bool;

    public function clearAttendance(): bool;

    public function restart(): bool;
}
