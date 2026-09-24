<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Device;
use App\Services\Device\DeviceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Fakes\FakeDeviceClient;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    private FakeDeviceClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();

        $this->client = new FakeDeviceClient;
        $this->app->instance(DeviceClient::class, $this->client);
    }

    private function pullDevice(): Device
    {
        return Device::create(['branch_id' => Branch::first()->id, 'name' => 'Gate', 'model' => 'K40', 'connection_mode' => 'pull', 'ip_address' => '192.168.1.201', 'port' => 4370]);
    }

    private function pushDevice(): Device
    {
        return Device::create(['branch_id' => Branch::first()->id, 'name' => 'Face', 'model' => 'SpeedFace-V5L', 'connection_mode' => 'push', 'serial_number' => 'CKJ1234']);
    }

    public function test_admin_can_add_device(): void
    {
        $this->actingAs($this->admin)->post(route('admin.devices.store'), [
            'name' => 'Main Gate',
            'branch_id' => Branch::first()->id,
            'model' => 'K40',
            'connection_mode' => 'pull',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('devices', ['name' => 'Main Gate', 'ip_address' => '192.168.1.201']);
    }

    public function test_sync_pulls_logs_and_builds_attendance(): void
    {
        $employee = $this->makeEmployee();
        $device = $this->pullDevice();
        $this->client->records = [
            ['user_id' => $employee->device_user_id, 'timestamp' => '2026-09-14 09:02:00', 'state' => 0, 'type' => 1],
            ['user_id' => $employee->device_user_id, 'timestamp' => '2026-09-14 18:01:00', 'state' => 1, 'type' => 1],
            ['user_id' => '9999', 'timestamp' => '2026-09-14 09:00:00', 'state' => 0, 'type' => 1],
        ];

        $this->actingAs($this->admin)->post(route('admin.devices.sync', $device))
            ->assertSessionHas('success', '3 new punches downloaded.');

        $this->assertSame(3, AttendanceLog::count());
        $this->assertSame('Fingerprint', AttendanceLog::first()->verify_type);
        $this->assertNotNull($device->fresh()->last_synced_at);
        $this->assertSame(Attendance::PRESENT, Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-14')->value('status'));

        $this->artisan('zk:sync')->assertSuccessful();
        $this->assertSame(3, AttendanceLog::count());
    }

    public function test_connection_error_is_saved(): void
    {
        $device = $this->pullDevice();
        $this->client->fail = true;

        $this->actingAs($this->admin)->post(route('admin.devices.test', $device))->assertSessionHas('error');
        $this->assertStringContainsString('Could not connect', $device->fresh()->last_error);
    }

    public function test_push_employees_to_pull_device(): void
    {
        $this->makeEmployee();
        $this->makeEmployee();
        $device = $this->pullDevice();

        $this->actingAs($this->admin)->post(route('admin.devices.push-users', $device))->assertSessionHas('success', '2 users sent to the device.');
        $this->assertCount(2, $this->client->users);
    }

    public function test_adms_handshake_and_attendance_upload(): void
    {
        $employee = $this->makeEmployee();
        $device = $this->pushDevice();

        $this->get('/iclock/cdata?SN=CKJ1234&options=all&pushver=2.4.1')
            ->assertOk()
            ->assertSee('GET OPTION FROM: CKJ1234')
            ->assertSee('Realtime=1');

        $body = "{$employee->device_user_id}\t2026-09-14 08:58:00\t0\t15\t0\t0\n{$employee->device_user_id}\t2026-09-14 18:03:00\t1\t15\t0\t0\n";
        $this->call('POST', '/iclock/cdata?SN=CKJ1234&table=ATTLOG&Stamp=1', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body)
            ->assertOk()
            ->assertSee('OK: 2');

        $this->assertSame(2, AttendanceLog::where('device_id', $device->id)->count());
        $this->assertSame('Face', AttendanceLog::first()->verify_type);
        $this->assertSame(Attendance::PRESENT, Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-14')->value('status'));
        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_adms_commands_are_sent_and_confirmed(): void
    {
        $employee = $this->makeEmployee();
        $device = $this->pushDevice();

        $this->actingAs($this->admin)->post(route('admin.devices.push-users', $device));
        $command = $device->commands()->first();

        $this->get('/iclock/getrequest?SN=CKJ1234')
            ->assertOk()
            ->assertSee("C:{$command->id}:DATA UPDATE USERINFO PIN={$employee->device_user_id}", false);
        $this->assertSame('sent', $command->fresh()->status);

        $this->call('POST', '/iclock/devicecmd?SN=CKJ1234', [], [], [], ['CONTENT_TYPE' => 'text/plain'], "ID={$command->id}&Return=0&CMD=DATA")->assertSee('OK');
        $this->assertSame('done', $command->fresh()->status);

        $this->get('/iclock/getrequest?SN=CKJ1234')->assertSee('OK');
    }

    public function test_unknown_adms_device_is_listed_but_ignored(): void
    {
        $this->call('POST', '/iclock/cdata?SN=UNKNOWN1&table=ATTLOG', [], [], [], ['CONTENT_TYPE' => 'text/plain'], "1\t2026-09-14 09:00:00\t0\t1")->assertOk();

        $this->assertSame(0, AttendanceLog::count());
        $this->actingAs($this->admin)->get(route('admin.devices.index'))->assertSee('UNKNOWN1');
    }
}
