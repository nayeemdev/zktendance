@extends('layouts.app')

@section('title', 'Leave Types')

@section('content')
<x-page-header>
    <a href="{{ route('admin.leave-types.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Leave Type</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Days / Year</th><th>Credit</th><th>For</th><th>Paid</th><th>Carry Forward</th><th>Half Day</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($leaveTypes as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->code }}</td>
                    <td>{{ $type->days_per_year ?: 'Unlimited' }}</td>
                    <td>{{ ucfirst($type->accrual) }}</td>
                    <td>{{ $type->gender ? ucfirst($type->gender) : 'Everyone' }}</td>
                    <td>{{ $type->is_paid ? 'Yes' : 'No' }}@if($type->is_encashable) <span class="badge text-bg-info">Encashable</span>@endif</td>
                    <td>{{ $type->carry_forward_limit ? 'Up to '.$type->carry_forward_limit : 'No' }}</td>
                    <td>{{ $type->allow_half_day ? 'Yes' : 'No' }}</td>
                    <td><x-badge :status="$type->is_active ? 'active' : 'paused'" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.leave-types.edit', $type) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        @if ($type->is_active)
                            <x-delete-button :action="route('admin.leave-types.destroy', $type)" message="Disable this leave type?" />
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
