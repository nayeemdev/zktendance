<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AttendanceLog::with(['employee', 'device'])
            ->when($request->date, fn ($q, $date) => $q->whereDate('punched_at', $date))
            ->when($request->device_id, fn ($q, $id) => $q->where('device_id', $id))
            ->when($request->user_id, fn ($q, $id) => $q->where('device_user_id', $id))
            ->when($request->boolean('unmatched'), fn ($q) => $q->whereNull('employee_id'))
            ->latest('punched_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.attendance.logs', ['logs' => $logs, 'devices' => Device::pluck('name', 'id')]);
    }
}
