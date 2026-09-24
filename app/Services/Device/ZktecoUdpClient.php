<?php

namespace App\Services\Device;

use App\Models\Device;
use Jmrashed\Zkteco\Lib\ZKTeco;
use RuntimeException;

class ZktecoUdpClient implements DeviceClient
{
    private ?ZKTeco $zk = null;

    public function connect(Device $device): void
    {
        $this->zk = new ZKTeco($device->ip_address, (int) $device->port);
        socket_set_option($this->zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => (int) config('zkteco.timeout', 10), 'usec' => 0]);

        if (! $this->zk->connect()) {
            $this->zk = null;
            throw new RuntimeException("Could not connect to {$device->ip_address}:{$device->port}");
        }
    }

    public function disconnect(): void
    {
        $this->zk?->disconnect();
        $this->zk = null;
    }

    public function info(): array
    {
        return [
            'device_name' => $this->clean($this->client()->deviceName()),
            'serial_number' => $this->clean($this->client()->serialNumber()),
            'platform' => $this->clean($this->client()->platform()),
            'firmware' => $this->clean($this->client()->fmVersion()),
            'version' => $this->clean($this->client()->version()),
            'device_time' => $this->client()->getTime(),
        ];
    }

    public function attendance(): array
    {
        $this->client()->disableDevice();

        try {
            $records = $this->client()->getAttendance();
        } finally {
            $this->client()->enableDevice();
        }

        return array_map(fn ($row) => [
            'user_id' => trim((string) $row['id']),
            'timestamp' => $row['timestamp'],
            'state' => (int) $row['state'],
            'type' => (int) $row['type'],
        ], $records);
    }

    public function users(): array
    {
        return array_values(array_map(fn ($row) => [
            'uid' => (int) $row['uid'],
            'user_id' => trim((string) $row['userid']),
            'name' => trim((string) $row['name']),
        ], $this->client()->getUser()));
    }

    public function setUser(int $uid, string $userId, string $name): bool
    {
        return (bool) $this->client()->setUser($uid, $userId, mb_substr($name, 0, 24), '');
    }

    public function removeUser(int $uid): bool
    {
        return (bool) $this->client()->removeUser($uid);
    }

    public function setTime(string $time): bool
    {
        return (bool) $this->client()->setTime($time);
    }

    public function clearAttendance(): bool
    {
        return (bool) $this->client()->clearAttendance();
    }

    public function restart(): bool
    {
        return (bool) $this->client()->restart();
    }

    private function client(): ZKTeco
    {
        return $this->zk ?? throw new RuntimeException('Device is not connected.');
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(str_replace(chr(0), '', $value));

        return str_contains($value, '=') ? trim(substr($value, strpos($value, '=') + 1)) : $value;
    }
}
