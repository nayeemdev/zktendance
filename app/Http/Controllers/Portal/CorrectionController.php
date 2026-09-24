<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectionRequest;
use App\Services\CorrectionService;
use Illuminate\Http\Request;

class CorrectionController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.corrections.index', [
            'corrections' => $request->user()->employee->corrections()->latest()->paginate(20),
        ]);
    }

    public function create(Request $request)
    {
        return view('portal.corrections.create', ['date' => $request->input('date', today()->toDateString())]);
    }

    public function store(CorrectionRequest $request, CorrectionService $service)
    {
        $service->request($request->user()->employee, $request->validated());

        return redirect()->route('portal.corrections.index')->with('success', 'Correction request submitted.');
    }
}
