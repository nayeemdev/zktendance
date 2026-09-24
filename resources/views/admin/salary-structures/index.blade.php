@extends('layouts.app')

@section('title', 'Salary Structures')

@section('content')
<x-page-header subtitle="A structure splits an employee's gross salary into components.">
    <a href="{{ route('admin.salary-structures.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Structure</a>
</x-page-header>

<div class="row g-3">
    @foreach ($structures as $structure)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ $structure->name }}</span>
                    <span class="text-nowrap">
                        <a href="{{ route('admin.salary-structures.edit', $structure) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.salary-structures.destroy', $structure)" />
                    </span>
                </div>
                <div class="card-body">
                    <p class="small text-muted">{{ $structure->description }}</p>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Component</th><th>Rule</th><th class="text-end">On {{ money(50000) }}</th></tr></thead>
                        @foreach ($structure->items as $i => $item)
                            <tr>
                                <td>{{ $item->component->name }}</td>
                                <td class="small">{{ $item->calculation === 'fixed' ? money($item->value) : $item->value.' '.\App\Models\SalaryStructureItem::CALCULATIONS[$item->calculation] }}</td>
                                <td class="text-end">{{ money(collect($examples[$structure->id])->firstWhere('component_id', $item->salary_component_id)['amount'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
