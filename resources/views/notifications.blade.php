@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<x-page-header>
    <x-post-button :action="route('notifications.read-all')" label="Mark all as read" icon="check2-all" />
</x-page-header>

<div class="card col-lg-9">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.open', $notification->id) }}" class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'bg-primary-subtle' }}">
                <div class="d-flex justify-content-between">
                    <strong>{{ $notification->data['title'] }}</strong>
                    <span class="small text-muted">{{ $notification->created_at->format('d M Y h:i A') }}</span>
                </div>
                <div class="small">{{ $notification->data['message'] }}</div>
            </a>
        @empty
            <div class="list-group-item text-muted">No notifications yet.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
