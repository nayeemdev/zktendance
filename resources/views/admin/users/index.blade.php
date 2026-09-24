@extends('layouts.app')

@section('title', 'Admin Users')

@section('content')
<x-page-header subtitle="Admins manage everything. HR users manage employees, attendance, leave and payroll but not settings or devices. Branch managers see their branch and approve its leave and corrections. Employee logins are created from the employee profile.">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
</x-page-header>

<div class="card col-lg-9">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ \App\Models\User::ROLES[$user->role] }}@if($user->branch)<div class="small text-muted">{{ $user->branch->name }}</div>@endif</td>
                    <td><x-badge :status="$user->is_active ? 'active' : 'paused'" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('admin.users.destroy', $user)" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
