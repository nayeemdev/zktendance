@extends('layouts.app')

@section('title', 'Bonus & Deductions')

@section('content')
<x-page-header subtitle="One time earnings (festival bonus, arrear, incentive) or deductions (fine, advance) for a month.">
    <form class="d-flex gap-2">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control">
        <button class="btn btn-outline-secondary">Show</button>
    </form>
    <a href="{{ route('admin.adjustments.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add</a>
</x-page-header>

<div class="card col-lg-10">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Title</th><th>Type</th><th class="text-end">Amount</th><th></th></tr></thead>
            <tbody>
            @forelse ($adjustments as $adjustment)
                <tr>
                    <td>{{ $adjustment->employee->name }}</td>
                    <td>{{ $adjustment->title }}</td>
                    <td><span class="badge text-bg-{{ $adjustment->type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($adjustment->type) }}</span></td>
                    <td class="text-end">{{ money($adjustment->amount) }}</td>
                    <td class="text-end"><x-delete-button :action="route('admin.adjustments.destroy', $adjustment)" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nothing for {{ $month->format('F Y') }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
