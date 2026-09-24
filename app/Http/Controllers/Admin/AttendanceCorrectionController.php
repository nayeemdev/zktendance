<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $corrections = AttendanceCorrection::with(['employee', 'reviewer'])
            ->where('status', $request->input('status', 'pending'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.corrections.index', compact('corrections'));
    }

    public function approve(Request $request, AttendanceCorrection $correction, AttendanceService $attendance)
    {
        abort_unless($correction->status === 'pending', 422);

        $attendance->saveManual(
            $correction->employee,
            $correction->date,
            $correction->check_in ? substr($correction->check_in, 0, 5) : null,
            $correction->check_out ? substr($correction->check_out, 0, 5) : null,
            null,
            'Correction: '.$correction->reason,
        );

        $correction->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return back()->with('success', 'Correction approved and attendance updated.');
    }

    public function reject(Request $request, AttendanceCorrection $correction)
    {
        abort_unless($correction->status === 'pending', 422);

        $correction->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return back()->with('success', 'Correction rejected.');
    }
}
