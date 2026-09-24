<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceRequest;
use App\Http\Requests\DeviceUserImportRequest;
use App\Http\Requests\DeviceUserLinkRequest;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Services\DeviceService;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DeviceController extends Controller
{
    public function __construct(private DeviceService $service) {}

    public function index()
    {
        return view('admin.devices.index', [
            'devices' => Device::with('branch')->withCount('logs')->orderBy('name')->get(),
            'unknown' => Cache::get('adms_unknown_devices', []),
        ]);
    }

    public function create()
    {
        return view('admin.devices.form', [
            'device' => new Device(['port' => 4370, 'connection_mode' => Device::MODE_PULL, 'is_active' => true]),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(DeviceRequest $request)
    {
        $device = Device::create($request->validated());

        return redirect()->route('admin.devices.show', $device)->with('success', 'Device added.');
    }

    public function show(Device $device)
    {
        $device->load('branch');

        return view('admin.devices.show', [
            'device' => $device,
            'logs' => $device->logs()->with('employee')->latest('punched_at')->limit(20)->get(),
            'commands' => $device->commands()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Device $device)
    {
        return view('admin.devices.form', ['device' => $device, 'branches' => Branch::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(DeviceRequest $request, Device $device)
    {
        $device->update($request->validated());

        return redirect()->route('admin.devices.show', $device)->with('success', 'Device updated.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('admin.devices.index')->with('success', 'Device removed. Its punches are kept.');
    }

    public function test(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $info = $this->service->testConnection($device);

            return 'Connected. '.collect($info)->filter()->map(fn ($v, $k) => str_replace('_', ' ', $k).': '.$v)->implode(', ');
        });
    }

    public function sync(Device $device)
    {
        return $this->attempt(fn () => $this->service->pullAttendance($device).' new punches downloaded.');
    }

    public function syncTime(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $this->service->syncTime($device);

            return $device->isPush() ? 'Time sync command queued.' : 'Device time updated.';
        });
    }

    public function pushUsers(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $count = $this->service->pushEmployees($device);

            return $device->isPush() ? "{$count} users queued for the device." : "{$count} users sent to the device.";
        });
    }

    public function users(Device $device)
    {
        return view('admin.devices.users', [
            'device' => $device,
            'users' => $device->users()->with('employee')->get()->sortBy(fn ($u) => (int) $u->user_id)->values(),
            'unlinked' => Employee::active()->whereNull('device_user_id')->orderBy('name')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->employee_code} - {$e->name}"]),
        ]);
    }

    public function refreshUsers(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $count = $this->service->refreshUsers($device);

            return $device->isPush()
                ? 'The device was asked to upload its users. Refresh this page in a minute.'
                : "{$count} users read from the device.";
        });
    }

    public function importUsers(DeviceUserImportRequest $request, Device $device, EmployeeService $employees)
    {
        $count = $employees->importFromDevice($device, $request->validated('user_ids'));

        return back()->with('success', "{$count} employees created. Complete their details and salary from the Employees page.");
    }

    public function linkUser(DeviceUserLinkRequest $request, Device $device, EmployeeService $employees)
    {
        $employee = Employee::findOrFail($request->validated('employee_id'));
        $employees->linkDeviceUser($employee, $request->validated('user_id'));

        return back()->with('success', "{$employee->name} is now linked to device user {$request->validated('user_id')}.");
    }

    public function restart(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $this->service->restart($device);

            return 'Restart command sent.';
        });
    }

    public function clearLogs(Device $device)
    {
        return $this->attempt(function () use ($device) {
            $this->service->clearLogs($device);

            return 'Device logs cleared. Downloaded punches are kept in the system.';
        });
    }

    private function attempt(callable $action)
    {
        try {
            return back()->with('success', $action());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
