@extends('layouts.app')

@section('title', $notice->exists ? 'Edit Notice' : 'New Notice')

@section('content')
<div class="card col-lg-8">
    <div class="card-body">
        <form method="POST" action="{{ $notice->exists ? route('admin.notices.update', $notice) : route('admin.notices.store') }}">
            @csrf
            @if ($notice->exists) @method('PUT') @endif
            <x-input name="title" label="Title" :value="$notice->title" required />
            <x-textarea name="body" label="Message" :value="$notice->body" rows="6" required />
            <div class="row">
                <x-select name="branch_id" label="Show To" :options="$branches" :value="$notice->branch_id" placeholder="All Branches" class="col-md-6 mb-3" />
                <x-input name="published_on" type="date" label="Publish Date" :value="$notice->published_on?->toDateString()" class="col-md-6 mb-3" required />
            </div>
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.notices.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
