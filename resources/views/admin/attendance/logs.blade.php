@extends('layouts.app')

@section('title', 'Device Punches')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-3"><input type="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        <x-select name="device_id" :options="$devices" :value="request('device_id')" placeholder="All Devices" class="col-md-3" />
        <div class="col-md-2"><input name="user_id" value="{{ request('user_id') }}" class="form-control" placeholder="Device User ID"></div>
        <div class="col-md-2 form-check ms-2">
            <input type="checkbox" class="form-check-input" name="unmatched" value="1" id="unmatched" @checked(request('unmatched'))>
            <label for="unmatched" class="form-check-label">Only not linked</label>
        </div>
        <div class="col-md-1"><button class="btn btn-secondary w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Time</th><th>Device</th><th>Device User ID</th><th>Employee</th><th>Verify</th><th>State</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->punched_at->format('d M Y h:i:s A') }}</td>
                    <td>{{ $log->device?->name ?? 'Manual' }}</td>
                    <td>{{ $log->device_user_id }}</td>
                    <td>{!! $log->employee ? e($log->employee->employee_code.' - '.$log->employee->name) : '<span class="text-danger">Not linked</span>' !!}</td>
                    <td>{{ $log->verify_type }}</td>
                    <td>{{ $log->state }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No punches found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
