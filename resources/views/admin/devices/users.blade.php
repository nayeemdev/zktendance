@extends('layouts.app')

@section('title', 'Users on '.$device->name)

@section('content')
<x-page-header :subtitle="$users->count().' users stored for this device'">
    <x-post-button :action="route('admin.devices.users.refresh', $device)" :label="$device->isPush() ? 'Ask Device to Upload Users' : 'Read Users from Device'" icon="refresh" />
    <a href="{{ route('admin.devices.show', $device) }}" class="btn btn-light">Back</a>
</x-page-header>

@if ($users->isEmpty())
    <div class="alert alert-info">
        No users yet. {{ $device->isPush() ? 'Push devices upload their users when asked, or when a user is added on the device.' : 'Press "Read Users from Device".' }}
    </div>
@endif

<form method="POST" action="{{ route('admin.devices.users.import', $device) }}" id="importForm">
    @csrf
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
            <tr>
                <th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.import-check').forEach(c => c.checked = this.checked)"></th>
                <th>User ID</th><th>Name on Device</th><th>Employee</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>
                        @unless ($user->employee)
                            <input type="checkbox" class="form-check-input import-check" name="user_ids[]" value="{{ $user->user_id }}" form="importForm">
                        @endunless
                    </td>
                    <td>{{ $user->user_id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>
                        @if ($user->employee)
                            <a href="{{ route('admin.employees.show', $user->employee) }}">{{ $user->employee->employee_code }} - {{ $user->employee->name }}</a>
                        @elseif ($unlinked->isNotEmpty())
                            <form method="POST" action="{{ route('admin.devices.users.link', $device) }}" class="d-flex gap-1">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->user_id }}">
                                <select name="employee_id" class="form-select form-select-sm" style="max-width: 260px" required>
                                    <option value="">Link to existing employee</option>
                                    @foreach ($unlinked as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Link</button>
                            </form>
                        @else
                            <span class="text-danger small">Not linked</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($users->contains(fn ($u) => ! $u->employee))
    <button class="btn btn-primary mt-3" form="importForm"><i class="hgi-stroke hgi-user-add-01"></i> Import Selected as New Employees</button>
    <div class="form-text">New employees join the {{ $device->branch->name }} branch with today's joining date. Complete their details and salary afterwards.</div>
@endif
@endsection
