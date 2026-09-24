<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['employees', 'devices'])->orderBy('name')->get();

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.form', ['branch' => new Branch(['weekend_days' => [5], 'is_active' => true])]);
    }

    public function store(BranchRequest $request)
    {
        Branch::create($request->validated());

        return redirect()->route('admin.branches.index')->with('success', 'Branch created.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.form', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->employees()->exists() || $branch->devices()->exists()) {
            return back()->with('error', 'Move employees and devices out of this branch before deleting it.');
        }

        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }
}
