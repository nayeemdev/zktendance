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

<div class="card col-xl-9">
    <div class="card-body">
        @include('payslips.content')
    </div>
</div>
@endsection
