@extends('layouts.app')

@section('title', 'Expiring Documents')

@section('content')
<x-page-header subtitle="Documents that expired or expire in the next 30 days. Upload documents from each employee's profile." />

<div class="card col-lg-10">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Document</th><th>Expires</th><th></th></tr></thead>
            <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td><a href="{{ route('admin.employees.show', $document->employee) }}">{{ $document->employee->name }}</a></td>
                    <td><a href="{{ route('admin.documents.download', $document) }}">{{ $document->title }}</a></td>
                    <td><span class="badge text-bg-{{ $document->isExpired() ? 'danger' : 'warning' }}">{{ $document->expires_on->format('d M Y') }}</span> <span class="small text-muted">{{ $document->expires_on->diffForHumans() }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.employees.show', $document->employee) }}" class="btn btn-sm btn-outline-secondary">Open profile</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nothing is expiring soon.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
