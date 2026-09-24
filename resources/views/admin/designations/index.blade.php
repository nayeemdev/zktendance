@extends('layouts.app')

@section('title', 'Designations')

@section('content')
<x-page-header>
    <a href="{{ route('admin.designations.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Designation</a>
</x-page-header>

<div class="card col-lg-8">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Employees</th><th></th></tr></thead>
            <tbody>
            @forelse ($designations as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->employees_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.designations.edit', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.designations.destroy', $item)" />
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
