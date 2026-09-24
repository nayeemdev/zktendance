<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup');
    Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/password', [ProfileController::class, 'update'])->name('profile.password');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::prefix('admin')->name('admin.')->middleware('role:admin,hr')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('employees', Admin\EmployeeController::class);
        Route::post('employees/{employee}/salaries', [Admin\EmployeeSalaryController::class, 'store'])->name('employees.salaries.store');
        Route::delete('salaries/{salary}', [Admin\EmployeeSalaryController::class, 'destroy'])->name('salaries.destroy');
        Route::resource('departments', Admin\DepartmentController::class)->except('show');
        Route::resource('designations', Admin\DesignationController::class)->except('show');
        Route::resource('shifts', Admin\ShiftController::class)->except('show');
        Route::get('roster', [Admin\RosterController::class, 'index'])->name('roster.index');
        Route::get('roster/create', [Admin\RosterController::class, 'create'])->name('roster.create');
        Route::post('roster', [Admin\RosterController::class, 'store'])->name('roster.store');
        Route::delete('roster/{assignment}', [Admin\RosterController::class, 'destroy'])->name('roster.destroy');
        Route::resource('holidays', Admin\HolidayController::class)->except('show');

        Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/create', [Admin\AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [Admin\AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/{attendance}/edit', [Admin\AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('attendance/{attendance}', [Admin\AttendanceController::class, 'update'])->name('attendance.update');
        Route::post('attendance/{attendance}/reset', [Admin\AttendanceController::class, 'reset'])->name('attendance.reset');
        Route::post('attendance-process', [Admin\AttendanceController::class, 'process'])->name('attendance.process');
        Route::get('punches', [Admin\AttendanceLogController::class, 'index'])->name('punches.index');

        Route::get('corrections', [Admin\AttendanceCorrectionController::class, 'index'])->name('corrections.index');
        Route::post('corrections/{correction}/approve', [Admin\AttendanceCorrectionController::class, 'approve'])->name('corrections.approve');
        Route::post('corrections/{correction}/reject', [Admin\AttendanceCorrectionController::class, 'reject'])->name('corrections.reject');

        Route::resource('leave-types', Admin\LeaveTypeController::class)->except('show');
        Route::get('leaves', [Admin\LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('leaves/create', [Admin\LeaveRequestController::class, 'create'])->name('leaves.create');
        Route::post('leaves', [Admin\LeaveRequestController::class, 'store'])->name('leaves.store');
        Route::post('leaves/{leave}/approve', [Admin\LeaveRequestController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/reject', [Admin\LeaveRequestController::class, 'reject'])->name('leaves.reject');
        Route::post('leaves/{leave}/cancel', [Admin\LeaveRequestController::class, 'cancel'])->name('leaves.cancel');
        Route::get('leave-balances', [Admin\LeaveBalanceController::class, 'index'])->name('leave-balances.index');
        Route::post('leave-balances/allocate', [Admin\LeaveBalanceController::class, 'allocate'])->name('leave-balances.allocate');
        Route::put('leave-balances/{balance}', [Admin\LeaveBalanceController::class, 'update'])->name('leave-balances.update');

        Route::resource('salary-components', Admin\SalaryComponentController::class)->except('show');
        Route::resource('salary-structures', Admin\SalaryStructureController::class)->except('show');
        Route::resource('overtime-rules', Admin\OvertimeRuleController::class)->except('show');
        Route::resource('loans', Admin\LoanController::class)->except('show');
        Route::resource('adjustments', Admin\PayrollAdjustmentController::class)->only(['index', 'create', 'store', 'destroy']);

        Route::resource('payroll', Admin\PayrollRunController::class)->except(['edit', 'update']);
        Route::post('payroll/{payroll}/regenerate', [Admin\PayrollRunController::class, 'regenerate'])->name('payroll.regenerate');
        Route::post('payroll/{payroll}/approve', [Admin\PayrollRunController::class, 'approve'])->name('payroll.approve');
        Route::post('payroll/{payroll}/pay', [Admin\PayrollRunController::class, 'pay'])->name('payroll.pay');
        Route::post('payroll/{payroll}/email', [Admin\PayrollRunController::class, 'email'])->name('payroll.email');
        Route::get('payroll/{payroll}/export', [Admin\PayrollRunController::class, 'export'])->name('payroll.export');
        Route::get('payslips/{payslip}', [Admin\PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [Admin\PayslipController::class, 'pdf'])->name('payslips.pdf');
        Route::post('payslips/{payslip}/email', [Admin\PayslipController::class, 'email'])->name('payslips.email');

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/monthly-summary', [Admin\ReportController::class, 'monthlySummary'])->name('reports.monthly-summary');
        Route::get('reports/monthly-sheet', [Admin\ReportController::class, 'monthlySheet'])->name('reports.monthly-sheet');
        Route::get('reports/late', [Admin\ReportController::class, 'late'])->name('reports.late');

        Route::resource('notices', Admin\NoticeController::class)->except('show');

        Route::middleware('role:admin')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::resource('users', Admin\UserController::class)->except('show');
            Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::resource('branches', Admin\BranchController::class)->except('show');
            Route::resource('devices', Admin\DeviceController::class);
            Route::controller(Admin\DeviceController::class)->prefix('devices/{device}')->name('devices.')->group(function () {
                Route::post('test', 'test')->name('test');
                Route::post('sync', 'sync')->name('sync');
                Route::post('sync-time', 'syncTime')->name('sync-time');
                Route::post('push-users', 'pushUsers')->name('push-users');
                Route::get('users', 'users')->name('users');
                Route::post('restart', 'restart')->name('restart');
                Route::post('clear-logs', 'clearLogs')->name('clear-logs');
            });
        });
    });

    Route::prefix('portal')->name('portal.')->middleware('employee')->group(function () {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('attendance', [Portal\AttendanceController::class, 'index'])->name('attendance');
        Route::get('leaves', [Portal\LeaveController::class, 'index'])->name('leaves.index');
        Route::get('leaves/create', [Portal\LeaveController::class, 'create'])->name('leaves.create');
        Route::post('leaves', [Portal\LeaveController::class, 'store'])->name('leaves.store');
        Route::post('leaves/{leave}/cancel', [Portal\LeaveController::class, 'cancel'])->name('leaves.cancel');
        Route::get('corrections', [Portal\CorrectionController::class, 'index'])->name('corrections.index');
        Route::get('corrections/create', [Portal\CorrectionController::class, 'create'])->name('corrections.create');
        Route::post('corrections', [Portal\CorrectionController::class, 'store'])->name('corrections.store');
        Route::get('payslips', [Portal\PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}', [Portal\PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [Portal\PayslipController::class, 'pdf'])->name('payslips.pdf');
    });
});
