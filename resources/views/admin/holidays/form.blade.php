@extends('layouts.app')

@section('title', $holiday->exists ? 'Edit Holiday' : 'Add Holiday')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ $holiday->exists ? route('admin.holidays.update', $holiday) : route('admin.holidays.store') }}">
            @csrf
            @if ($holiday->exists) @method('PUT') @endif
            <x-input name="name" label="Holiday Name" :value="$holiday->name" required />
            <div class="row">
                <x-input name="date" type="date" :label="$holiday->exists ? 'Date' : 'From Date'" :value="$holiday->date?->toDateString()" class="col-md-6 mb-3" required />
                @unless ($holiday->exists)
                    <x-input name="end_date" type="date" label="To Date (optional)" class="col-md-6 mb-3" help="For holidays that run several days, like Eid." />
                @endunless
            </div>
            <x-select name="branch_id" label="Branch" :options="$branches" :value="$holiday->branch_id" placeholder="All Branches" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.holidays.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
