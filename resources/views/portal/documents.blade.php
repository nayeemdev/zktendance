@extends('layouts.app')

@section('title', 'My Documents')

@section('content')
<div class="card col-lg-8">
    <div class="list-group list-group-flush">
        @forelse ($documents as $document)
            <a href="{{ route('portal.documents.download', $document) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="hgi-stroke hgi-file-01 me-2"></i>{{ $document->title }} <span class="small text-muted">{{ $document->original_name }}</span></span>
                @if ($document->expires_on)
                    <span class="badge text-bg-{{ $document->isExpired() ? 'danger' : ($document->expiresSoon() ? 'warning' : 'light') }}">Expires {{ $document->expires_on->format('d M Y') }}</span>
                @endif
            </a>
        @empty
            <div class="list-group-item text-muted">No documents shared with you.</div>
        @endforelse
    </div>
</div>
@endsection
