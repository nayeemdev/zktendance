@extends('layouts.app')

@section('title', $branch->exists ? 'Edit Branch' : 'Add Branch')

@section('content')
<div class="card col-lg-8">
    <div class="card-body">
        <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
            @csrf
            @if ($branch->exists) @method('PUT') @endif
            <div class="row">
                <x-input name="name" label="Branch Name" :value="$branch->name" class="col-md-8 mb-3" required />
                <x-input name="code" label="Code" :value="$branch->code" class="col-md-4 mb-3" required />
                <x-input name="phone" label="Phone" :value="$branch->phone" class="col-md-4 mb-3" />
                <x-input name="address" label="Address" :value="$branch->address" class="col-md-8 mb-3" />
            </div>
            <label class="form-label">Weekend Days</label>
            <div class="mb-3">
                @foreach (\Illuminate\Support\Carbon::getDays() as $i => $day)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="weekend_days[]" value="{{ $i }}" id="day{{ $i }}" @checked(in_array($i, old('weekend_days', $branch->weekend_days ?? [])))>
                        <label class="form-check-label" for="day{{ $i }}">{{ $day }}</label>
                    </div>
                @endforeach
            </div>
            <x-checkbox name="is_active" label="Active" :checked="$branch->is_active" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.branches.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
