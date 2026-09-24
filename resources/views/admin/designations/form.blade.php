@extends('layouts.app')

@section('title', $designation->exists ? 'Edit Designation' : 'Add Designation')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ $designation->exists ? route('admin.designations.update', $designation) : route('admin.designations.store') }}">
            @csrf
            @if ($designation->exists) @method('PUT') @endif
            <x-input name="name" label="Name" :value="$designation->name" required />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.designations.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
