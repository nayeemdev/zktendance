@extends('layouts.app')

@section('title', 'Users on '.$device->name)

@section('content')
<x-page-header :subtitle="count($users).' users stored on the device'">
    <a href="{{ route('admin.devices.show', $device) }}" class="btn btn-light">Back</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>UID</th><th>User ID</th><th>Name on Device</th><th>Linked Employee</th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user['uid'] }}</td>
                    <td>{{ $user['user_id'] }}</td>
                    <td>{{ $user['name'] }}</td>
                    <td>
                        @if ($employee = $employees[$user['user_id']] ?? null)
                            <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->employee_code }} - {{ $employee->name }}</a>
                        @else
                            <span class="text-danger">Not linked.</span> Set this User ID as an employee's Device User ID.
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
