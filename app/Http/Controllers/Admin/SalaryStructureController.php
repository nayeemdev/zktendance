<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryStructureRequest;
use App\Models\EmployeeSalary;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Services\SalaryService;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends Controller
{
    public function index(SalaryService $salary)
    {
        $structures = SalaryStructure::with('items.component')->withCount('items')->orderBy('name')->get();
        $examples = $structures->mapWithKeys(fn ($s) => [$s->id => $salary->breakdown($s, 50000)]);

        return view('admin.salary-structures.index', compact('structures', 'examples'));
    }

    public function create()
    {
        return view('admin.salary-structures.form', ['structure' => new SalaryStructure, 'components' => SalaryComponent::orderBy('sort_order')->get()]);
    }

    public function store(SalaryStructureRequest $request)
    {
        $this->save(new SalaryStructure, $request->validated());

        return redirect()->route('admin.salary-structures.index')->with('success', 'Salary structure created.');
    }

    public function edit(SalaryStructure $salaryStructure)
    {
        return view('admin.salary-structures.form', [
            'structure' => $salaryStructure->load('items'),
            'components' => SalaryComponent::orderBy('sort_order')->get(),
        ]);
    }

    public function update(SalaryStructureRequest $request, SalaryStructure $salaryStructure)
    {
        $this->save($salaryStructure, $request->validated());

        return redirect()->route('admin.salary-structures.index')->with('success', 'Salary structure updated.');
    }

    public function destroy(SalaryStructure $salaryStructure)
    {
        if (EmployeeSalary::where('salary_structure_id', $salaryStructure->id)->exists()) {
            return back()->with('error', 'This structure is assigned to employees.');
        }

        $salaryStructure->delete();

        return back()->with('success', 'Salary structure deleted.');
    }

    private function save(SalaryStructure $structure, array $data): void
    {
        DB::transaction(function () use ($structure, $data) {
            $structure->fill(['name' => $data['name'], 'description' => $data['description'] ?? null])->save();
            $structure->items()->delete();

            foreach ($data['items'] as $componentId => $item) {
                if (! empty($item['enabled'])) {
                    $structure->items()->create([
                        'salary_component_id' => $componentId,
                        'calculation' => $item['calculation'],
                        'value' => $item['value'] ?? 0,
                    ]);
                }
            }
        });
    }
}
