<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Services\CorrectionService;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function __construct(private CorrectionService $service) {}

    public function index(Request $request)
    {
        $corrections = AttendanceCorrection::with(['employee', 'reviewer'])
            ->where('status', $request->input('status', 'pending'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.corrections.index', compact('corrections'));
    }

    public function approve(Request $request, AttendanceCorrection $correction)
    {
        $this->service->approve($correction, $request->user());

        return back()->with('success', 'Correction approved and attendance updated.');
    }

    public function reject(Request $request, AttendanceCorrection $correction)
    {
        $this->service->reject($correction, $request->user());

        return back()->with('success', 'Correction rejected.');
    }
}
