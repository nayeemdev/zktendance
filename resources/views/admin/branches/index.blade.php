@extends('layouts.app')

@section('title', 'Branches')

@section('content')
<x-page-header subtitle="Each branch can have its own weekends, holidays, devices and overtime rule.">
    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Branch</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Weekend</th><th>Employees</th><th>Devices</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($branches as $branch)
                <tr>
                    <td>{{ $branch->name }}<div class="small text-muted">{{ $branch->address }}</div></td>
                    <td>{{ $branch->code }}</td>
                    <td>{{ collect($branch->weekend_days)->map(fn ($d) => \Illuminate\Support\Carbon::getDays()[$d])->implode(', ') ?: 'None' }}</td>
                    <td>{{ $branch->employees_count }}</td>
                    <td>{{ $branch->devices_count }}</td>
                    <td><x-badge :status="$branch->is_active ? 'active' : 'paused'" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.branches.destroy', $branch)" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
