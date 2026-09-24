<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignationRequest;
use App\Models\Designation;

class DesignationController extends Controller
{
    public function index()
    {
        $designations = Designation::withCount('employees')->orderBy('name')->get();

        return view('admin.designations.index', compact('designations'));
    }

    public function create()
    {
        return view('admin.designations.form', ['designation' => new Designation]);
    }

    public function store(DesignationRequest $request)
    {
        Designation::create($request->validated());

        return redirect()->route('admin.designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation)
    {
        return view('admin.designations.form', compact('designation'));
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        $designation->update($request->validated());

        return redirect()->route('admin.designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
