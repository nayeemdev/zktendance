<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\Employee;
use App\Services\AttendanceLogService;
use App\Services\Device\DeviceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Fakes\FakeDeviceClient;
use Tests\TestCase;

class DeviceUserImportTest extends TestCase
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

    public function test_pull_device_users_can_be_imported_and_linked(): void
    {
        $device = Device::create(['branch_id' => Branch::first()->id, 'name' => 'Gate', 'connection_mode' => 'pull', 'ip_address' => '10.0.0.2', 'port' => 4370]);
        $existing = $this->makeEmployee(['device_user_id' => null, 'name' => 'Existing Staff']);
        $this->client->users = [
            ['uid' => 1, 'user_id' => '11', 'name' => 'Jamal'],
            ['uid' => 2, 'user_id' => '12', 'name' => 'Kamal'],
            ['uid' => 3, 'user_id' => '13', 'name' => ''],
        ];
        app(AttendanceLogService::class)->store($device, [['user_id' => '12', 'timestamp' => '2026-09-14 09:00:00']]);

        $this->actingAs($this->admin)->post(route('admin.devices.users.refresh', $device))->assertSessionHas('success', '3 users read from the device.');
        $this->actingAs($this->admin)->get(route('admin.devices.users', $device))->assertOk()->assertSee('Jamal')->assertSee('Existing Staff');

        $this->actingAs($this->admin)->post(route('admin.devices.users.import', $device), ['user_ids' => ['11', '13']])->assertSessionHas('success');
        $this->assertSame('Jamal', Employee::where('device_user_id', '11')->value('name'));
        $this->assertSame('Device User 13', Employee::where('device_user_id', '13')->value('name'));

        $this->actingAs($this->admin)->post(route('admin.devices.users.link', $device), ['user_id' => '12', 'employee_id' => $existing->id]);
        $this->assertSame('12', $existing->fresh()->device_user_id);
        $this->assertSame($existing->id, AttendanceLog::first()->employee_id);

        $this->actingAs($this->admin)->post(route('admin.devices.users.import', $device), ['user_ids' => ['11']]);
        $this->assertSame(1, Employee::where('device_user_id', '11')->count());
    }

    public function test_push_device_uploads_users_through_adms(): void
    {
        $device = Device::create(['branch_id' => Branch::first()->id, 'name' => 'Face', 'connection_mode' => 'push', 'serial_number' => 'SN1']);

        $this->actingAs($this->admin)->post(route('admin.devices.users.refresh', $device))->assertSessionHas('success');
        $this->assertSame('DATA QUERY USERINFO', $device->commands()->value('command'));

        $body = "USER PIN=21\tName=Rina\tPri=0\tPasswd=\tCard=\tGrp=1\nUSER PIN=22\tName=Mina\tPri=0\nOPLOG 4\t0\t2026-09-14 09:00:00\t0\t0\t0\t0\n";
        $this->call('POST', '/iclock/cdata?SN=SN1&table=OPERLOG', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body)->assertSee('OK: 2');

        $this->assertSame(['Rina', 'Mina'], DeviceUser::orderBy('user_id')->pluck('name')->all());
    }
}
