@extends('layouts.app')

@section('title', $department->exists ? 'Edit Department' : 'Add Department')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ $department->exists ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
            @csrf
            @if ($department->exists) @method('PUT') @endif
            <x-input name="name" label="Name" :value="$department->name" required />
            <x-input name="code" label="Code" :value="$department->code" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.departments.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
