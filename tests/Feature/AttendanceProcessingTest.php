<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Holiday;
use App\Models\Shift;
use App\Services\AttendanceLogService;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
    }

    private function punch($employee, array $times): void
    {
        app(AttendanceLogService::class)->store(null, array_map(
            fn ($t) => ['user_id' => $employee->device_user_id, 'timestamp' => $t],
            $times
        ));
    }

    private function day($employee, string $date): Attendance
    {
        return Attendance::where('employee_id', $employee->id)->whereDate('date', $date)->firstOrFail();
    }

    public function test_on_time_employee_is_present(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, ['2026-09-14 08:55:00', '2026-09-14 13:00:00', '2026-09-14 18:05:00']);

        $day = $this->day($employee, '2026-09-14');
        $this->assertSame(Attendance::PRESENT, $day->status);
        $this->assertSame('08:55', $day->check_in->format('H:i'));
        $this->assertSame('18:05', $day->check_out->format('H:i'));
        $this->assertSame(0, $day->late_minutes);
        $this->assertSame(0, $day->overtime_minutes);
    }

    public function test_late_after_grace_period(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, ['2026-09-14 09:20:00', '2026-09-14 18:00:00']);

        $day = $this->day($employee, '2026-09-14');
        $this->assertSame(Attendance::LATE, $day->status);
        $this->assertSame(20, $day->late_minutes);
    }

    public function test_within_grace_is_not_late(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, ['2026-09-14 09:14:00', '2026-09-14 18:00:00']);

        $this->assertSame(Attendance::PRESENT, $this->day($employee, '2026-09-14')->status);
    }

    public function test_short_day_is_half_day_and_early_leave(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, ['2026-09-14 09:00:00', '2026-09-14 12:00:00']);

        $day = $this->day($employee, '2026-09-14');
        $this->assertSame(Attendance::HALF_DAY, $day->status);
        $this->assertSame(360, $day->early_leave_minutes);
    }

    public function test_overtime_is_rounded_by_rule(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, ['2026-09-14 09:00:00', '2026-09-14 19:40:00']);

        $this->assertSame(90, $this->day($employee, '2026-09-14')->overtime_minutes);
    }

    public function test_no_punch_is_absent_and_weekend_is_weekend(): void
    {
        $employee = $this->makeEmployee();
        app(AttendanceService::class)->processRange(Carbon::parse('2026-09-10'), Carbon::parse('2026-09-11'));

        $this->assertSame(Attendance::ABSENT, $this->day($employee, '2026-09-10')->status);
        $this->assertSame(Attendance::WEEKEND, $this->day($employee, '2026-09-11')->status);
    }

    public function test_work_on_holiday_counts_as_overtime(): void
    {
        $employee = $this->makeEmployee();
        Holiday::create(['name' => 'Test Holiday', 'date' => '2026-09-15']);
        $this->punch($employee, ['2026-09-15 10:00:00', '2026-09-15 13:10:00']);

        $day = $this->day($employee, '2026-09-15');
        $this->assertSame(Attendance::HOLIDAY, $day->status);
        $this->assertSame(180, $day->overtime_minutes);
    }

    public function test_night_shift_across_midnight(): void
    {
        $night = Shift::create(['name' => 'Night', 'start_time' => '22:00', 'end_time' => '06:00', 'grace_minutes' => 10, 'half_day_minutes' => 240, 'break_minutes' => 0]);
        $employee = $this->makeEmployee(['shift_id' => $night->id]);
        $this->punch($employee, ['2026-09-14 21:55:00', '2026-09-15 06:02:00']);

        $day = $this->day($employee, '2026-09-14');
        $this->assertSame(Attendance::PRESENT, $day->status);
        $this->assertSame('2026-09-15 06:02', $day->check_out->format('Y-m-d H:i'));
    }

    public function test_manual_attendance_is_not_overwritten(): void
    {
        $employee = $this->makeEmployee();
        app(AttendanceService::class)->saveManual($employee, Carbon::parse('2026-09-14'), '09:00', '18:00', null, 'Card forgotten');
        app(AttendanceService::class)->processDate(Carbon::parse('2026-09-14'));

        $day = $this->day($employee, '2026-09-14');
        $this->assertTrue($day->is_manual);
        $this->assertSame(Attendance::PRESENT, $day->status);
    }

    public function test_duplicate_punches_are_ignored_and_late_linking_works(): void
    {
        $employee = $this->makeEmployee(['device_user_id' => null]);
        $service = app(AttendanceLogService::class);

        $records = [['user_id' => '555', 'timestamp' => '2026-09-14 09:00:00']];
        $this->assertSame(1, $service->store(null, $records));
        $this->assertSame(0, $service->store(null, $records));

        $employee->update(['device_user_id' => '555']);
        $service->linkEmployee($employee);
        $this->assertSame(1, AttendanceLog::where('employee_id', $employee->id)->count());
    }
}
