@extends('layouts.app')

@section('title', 'Holidays')

@section('content')
<x-page-header>
    <form class="d-flex gap-2">
        <input type="number" name="year" value="{{ $year }}" class="form-control" style="width: 110px">
        <button class="btn btn-outline-secondary">Show</button>
    </form>
    <a href="{{ route('admin.holidays.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Holiday</a>
</x-page-header>

<div class="card col-lg-10">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Day</th><th>Holiday</th><th>Branch</th><th></th></tr></thead>
            <tbody>
            @forelse ($holidays as $holiday)
                <tr>
                    <td>{{ $holiday->date->format('d M Y') }}</td>
                    <td>{{ $holiday->date->format('l') }}</td>
                    <td>{{ $holiday->name }}</td>
                    <td>{{ $holiday->branch?->name ?? 'All Branches' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a>
                        <x-delete-button :action="route('admin.holidays.destroy', $holiday)" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No holidays for {{ $year }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
