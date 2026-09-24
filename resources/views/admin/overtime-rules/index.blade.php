@extends('layouts.app')

@section('title', 'Overtime Rules')

@section('content')
<x-page-header subtitle="A branch rule overrides the company default. Hourly rate = (Basic or Gross) / divisor. The Bangladesh Labour Act uses Basic / 208 x 2.">
    <a href="{{ route('admin.overtime-rules.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Rule</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Applies To</th><th>Starts After</th><th>Rounding</th><th>Daily Max</th><th>Rate</th><th>Workday</th><th>Off Day</th><th>Approval</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($rules as $rule)
                <tr>
                    <td>{{ $rule->name }}</td>
                    <td>{{ $rule->branch?->name ?? 'Company default' }}</td>
                    <td>{{ $rule->min_minutes }} min</td>
                    <td>{{ $rule->rounding_minutes }} min</td>
                    <td>{{ $rule->max_daily_minutes ? minutes_to_hours($rule->max_daily_minutes).' hrs' : 'No limit' }}</td>
                    <td>{{ ucfirst($rule->rate_base) }} / {{ $rule->monthly_hours_divisor }}</td>
                    <td>x{{ $rule->workday_multiplier }}</td>
                    <td>x{{ $rule->offday_multiplier }}</td>
                    <td>{{ $rule->requires_approval ? 'Required' : 'Automatic' }}</td>
                    <td><x-badge :status="$rule->is_enabled ? 'active' : 'paused'" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.overtime-rules.edit', $rule) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('admin.overtime-rules.destroy', $rule)" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
