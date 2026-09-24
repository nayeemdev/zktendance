@extends('layouts.app')

@section('title', $structure->exists ? 'Edit Salary Structure' : 'Add Salary Structure')

@section('content')
@php($items = $structure->exists ? $structure->items->keyBy('salary_component_id') : collect())
<div class="card col-lg-9">
    <div class="card-body">
        <form method="POST" action="{{ $structure->exists ? route('admin.salary-structures.update', $structure) : route('admin.salary-structures.store') }}">
            @csrf
            @if ($structure->exists) @method('PUT') @endif
            <div class="row">
                <x-input name="name" label="Name" :value="$structure->name" class="col-md-5 mb-3" required />
                <x-input name="description" label="Description" :value="$structure->description" class="col-md-7 mb-3" />
            </div>

            <table class="table align-middle">
                <thead><tr><th>Use</th><th>Component</th><th>Calculation</th><th style="width: 160px">Value</th></tr></thead>
                <tbody>
                @foreach ($components as $row)
                    @php($item = $items[$row->id] ?? null)
                    <tr>
                        <td>
                            <input type="hidden" name="items[{{ $row->id }}][enabled]" value="0">
                            <input type="checkbox" class="form-check-input" name="items[{{ $row->id }}][enabled]" value="1" @checked(old("items.{$row->id}.enabled", (bool) $item))>
                        </td>
                        <td>{{ $row->name }} <span class="badge text-bg-{{ $row->type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($row->type) }}</span></td>
                        <td>
                            <select name="items[{{ $row->id }}][calculation]" class="form-select form-select-sm">
                                @foreach (\App\Models\SalaryStructureItem::CALCULATIONS as $key => $label)
                                    <option value="{{ $key }}" @selected(old("items.{$row->id}.calculation", $item?->calculation ?? 'percent_of_gross') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $row->id }}][value]" value="{{ old("items.{$row->id}.value", $item?->value) }}" class="form-control form-control-sm"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="small text-muted">Basic is calculated first, so other components can use % of Basic. Earnings should add up to 100% of gross.</p>
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.salary-structures.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
