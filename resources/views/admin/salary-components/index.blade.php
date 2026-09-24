@extends('layouts.app')

@section('title', 'Salary Components')

@section('content')
<x-page-header subtitle="Components are the lines on a payslip. Their amounts are set in salary structures.">
    <a href="{{ route('admin.salary-components.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Component</a>
</x-page-header>

<div class="card col-lg-9">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Type</th><th>Basic</th><th>Taxable</th><th>Order</th><th></th></tr></thead>
            <tbody>
            @foreach ($components as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td><span class="badge text-bg-{{ $item->type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($item->type) }}</span></td>
                    <td>{{ $item->is_basic ? 'Yes' : '' }}</td>
                    <td>{{ $item->is_taxable ? 'Yes' : 'No' }}</td>
                    <td>{{ $item->sort_order }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.salary-components.edit', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.salary-components.destroy', $item)" message="Delete this component? It will be removed from all structures." />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
