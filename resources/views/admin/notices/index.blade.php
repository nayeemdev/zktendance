@extends('layouts.app')

@section('title', 'Notices')

@section('content')
<x-page-header>
    <a href="{{ route('admin.notices.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> New Notice</a>
</x-page-header>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($notices as $notice)
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>{{ $notice->title }}</strong>
                        <span class="small text-muted">{{ $notice->published_on->format('d M Y') }} &middot; {{ $notice->branch?->name ?? 'All Branches' }}</span>
                    </div>
                    <div class="text-nowrap">
                        <a href="{{ route('admin.notices.edit', $notice) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.notices.destroy', $notice)" />
                    </div>
                </div>
                <div class="small mt-1">{!! nl2br(e($notice->body)) !!}</div>
            </div>
        @empty
            <div class="list-group-item text-muted">No notices yet.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notices->links() }}</div>
@endsection
