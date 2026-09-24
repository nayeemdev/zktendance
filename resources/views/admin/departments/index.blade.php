@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<x-page-header>
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Department</a>
</x-page-header>

<div class="card col-lg-8">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Employees</th><th></th></tr></thead>
            <tbody>
            @forelse ($departments as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->employees_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.departments.edit', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('admin.departments.destroy', $item)" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nothing added yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
