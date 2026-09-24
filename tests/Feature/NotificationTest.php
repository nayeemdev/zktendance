<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrection;
use App\Models\Branch;
use App\Models\Device;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();

        $employee = $this->makeEmployee();
        $this->employeeUser = User::create(['name' => $employee->name, 'email' => 'e@example.com', 'password' => 'password', 'role' => 'employee']);
        $employee->update(['user_id' => $this->employeeUser->id]);
    }

    public function test_leave_request_and_review_notify_both_sides(): void
    {
        Notification::fake();
        $casual = LeaveType::where('code', 'CL')->first();

        $this->actingAs($this->employeeUser)->post(route('portal.leaves.store'), [
            'leave_type_id' => $casual->id, 'start_date' => '2026-09-21', 'end_date' => '2026-09-22',
        ]);
        Notification::assertSentTo($this->admin, SystemNotification::class, fn ($n) => $n->title === 'New leave request');

        $this->actingAs($this->admin)->post(route('admin.leaves.approve', LeaveRequest::first()));
        Notification::assertSentTo($this->employeeUser, SystemNotification::class, fn ($n, $channels) => $n->title === 'Leave approved' && in_array('mail', $channels));
    }

    public function test_correction_request_and_review_notify(): void
    {
        Notification::fake();

        $this->actingAs($this->employeeUser)->post(route('portal.corrections.store'), [
            'date' => '2026-09-14', 'check_in' => '09:00', 'check_out' => '18:00', 'reason' => 'Device missed me',
        ]);
        Notification::assertSentTo($this->admin, SystemNotification::class, fn ($n) => $n->title === 'New attendance correction');

        $this->actingAs($this->admin)->post(route('admin.corrections.reject', AttendanceCorrection::first()));
        Notification::assertSentTo($this->employeeUser, SystemNotification::class, fn ($n) => $n->title === 'Attendance correction rejected');
    }

    public function test_bell_shows_and_opening_marks_read(): void
    {
        $this->employeeUser->notify(new SystemNotification('Leave approved', 'Your leave was approved.', route('portal.leaves.index'), false));

        $this->actingAs($this->employeeUser)->get(route('portal.dashboard'))->assertSee('Leave approved');

        $notification = $this->employeeUser->notifications()->first();
        $this->actingAs($this->employeeUser)->get(route('notifications.open', $notification->id))->assertRedirect(route('portal.leaves.index'));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($this->employeeUser)->get(route('notifications.index'))->assertOk();
    }

    public function test_offline_device_alerts_admin_once(): void
    {
        Notification::fake();
        $device = Device::create(['branch_id' => Branch::first()->id, 'name' => 'Gate', 'connection_mode' => 'pull', 'ip_address' => '10.0.0.5', 'port' => 4370, 'last_seen_at' => now()->subHour()]);

        $this->artisan('devices:check')->assertSuccessful();
        $this->artisan('devices:check')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, SystemNotification::class, 1);

        $device->update(['last_seen_at' => now()]);
        $this->artisan('devices:check');
        $this->assertNull($device->fresh()->offline_notified_at);
    }

    public function test_email_can_be_turned_off(): void
    {
        app(SettingService::class)->set(['email_notifications' => false]);

        $this->assertSame(['database'], (new SystemNotification('A', 'B'))->via($this->employeeUser));
    }
}
