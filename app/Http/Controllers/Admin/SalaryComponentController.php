<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryComponentRequest;
use App\Models\SalaryComponent;

class SalaryComponentController extends Controller
{
    public function index()
    {
        return view('admin.salary-components.index', ['components' => SalaryComponent::orderBy('type')->orderBy('sort_order')->get()]);
    }

    public function create()
    {
        return view('admin.salary-components.form', ['salaryComponent' => new SalaryComponent(['type' => 'earning', 'is_taxable' => true])]);
    }

    public function store(SalaryComponentRequest $request)
    {
        $this->save(new SalaryComponent, $request->validated());

        return redirect()->route('admin.salary-components.index')->with('success', 'Component created.');
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return view('admin.salary-components.form', ['salaryComponent' => $salaryComponent]);
    }

    public function update(SalaryComponentRequest $request, SalaryComponent $salaryComponent)
    {
        $this->save($salaryComponent, $request->validated());

        return redirect()->route('admin.salary-components.index')->with('success', 'Component updated.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();

        return back()->with('success', 'Component deleted.');
    }

    private function save(SalaryComponent $component, array $data): void
    {
        if (! empty($data['is_basic'])) {
            SalaryComponent::where('is_basic', true)->whereKeyNot($component->id)->update(['is_basic' => false]);
        }

        $component->fill($data)->save();
    }
}
