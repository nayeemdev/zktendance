@extends('layouts.app')

@section('title', 'Payslip')

@section('content')
@php($isAdmin = request()->routeIs('admin.*'))
<x-page-header>
    <a href="{{ $isAdmin ? route('admin.payslips.pdf', $payslip) : route('portal.payslips.pdf', $payslip) }}" class="btn btn-primary"><i class="bi bi-file-pdf"></i> Download PDF</a>
    @if ($isAdmin && ! $payslip->payrollRun->isDraft())
        <x-post-button :action="route('admin.payslips.email', $payslip)" label="Email" icon="envelope" />
    @endif
    <a href="{{ $isAdmin ? route('admin.payroll.show', $payslip->payrollRun) : route('portal.payslips.index') }}" class="btn btn-light">Back</a>
</x-page-header>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                @include('payslips.content')
            </div>
        </div>
    </div>

    @if ($isAdmin && $payslip->payrollRun->isDraft())
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Edit Lines</div>
                <div class="card-body">
                    @if ($payslip->edited_at)
                        <div class="alert alert-warning small py-2">Edited {{ $payslip->edited_at->diffForHumans() }}. Regenerating the payroll replaces these edits.</div>
                    @endif
                    @foreach ($payslip->items->sortBy('type') as $item)
                        <form method="POST" action="{{ route('admin.payslip-items.update', $item) }}" class="d-flex gap-1 mb-2">
                            @csrf
                            @method('PUT')
                            <span class="badge align-self-center text-bg-{{ $item->type === 'earning' ? 'success' : 'danger' }}">{{ $item->type === 'earning' ? '+' : '-' }}</span>
                            <input name="name" value="{{ $item->name }}" class="form-control form-control-sm">
                            <input name="amount" type="number" step="0.01" min="0" value="{{ $item->amount }}" class="form-control form-control-sm" style="width: 110px">
                            <button class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check"></i></button>
                        </form>
                    @endforeach
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @foreach ($payslip->items as $item)
                            <form method="POST" action="{{ route('admin.payslip-items.destroy', $item) }}" data-confirm="Remove {{ $item->name }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-light border" title="Remove"><i class="bi bi-x"></i> {{ \Illuminate\Support\Str::limit($item->name, 18) }}</button>
                            </form>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.payslips.items.store', $payslip) }}" class="border-top pt-3">
                        @csrf
                        <div class="fw-semibold small mb-2">Add a line</div>
                        <x-select name="type" :options="['earning' => 'Earning', 'deduction' => 'Deduction']" class="mb-2" />
                        <x-input name="name" placeholder="Name, for example Arrear" class="mb-2" required />
                        <x-input name="amount" type="number" step="0.01" min="0" placeholder="Amount" class="mb-2" required />
                        <button class="btn btn-sm btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
