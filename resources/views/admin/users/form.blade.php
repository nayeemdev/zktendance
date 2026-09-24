@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <x-input name="name" label="Name" :value="$user->name" required />
            <x-input name="email" type="email" label="Email" :value="$user->email" required />
            <x-select name="role" label="Role" :options="['admin' => 'Admin', 'hr' => 'HR', 'manager' => 'Branch Manager']" :value="$user->role" required />
            <x-select name="branch_id" label="Managed Branch" :options="$branches" :value="$user->branch_id" placeholder="Select for Branch Manager" help="A branch manager sees and approves only this branch." />
            <x-input name="password" type="password" label="Password" :required="! $user->exists" :help="$user->exists ? 'Leave empty to keep the current password.' : null" />
            <x-checkbox name="is_active" label="Active" :checked="$user->is_active" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
