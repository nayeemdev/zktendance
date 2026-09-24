@extends('layouts.app')

@section('title', $rule->exists ? 'Edit Overtime Rule' : 'Add Overtime Rule')

@section('content')
<div class="card col-lg-8">
    <div class="card-body">
        <form method="POST" action="{{ $rule->exists ? route('admin.overtime-rules.update', $rule) : route('admin.overtime-rules.store') }}">
            @csrf
            @if ($rule->exists) @method('PUT') @endif
            <div class="row">
                <x-input name="name" label="Name" :value="$rule->name" class="col-md-6 mb-3" required />
                <x-select name="branch_id" label="Applies To" :options="$branches" :value="$rule->branch_id" placeholder="Company default" class="col-md-6 mb-3" />
                <x-input name="min_minutes" type="number" label="Count OT after (minutes past shift end)" :value="$rule->min_minutes" class="col-md-4 mb-3" required />
                <x-input name="rounding_minutes" type="number" label="Round down to (minutes)" :value="$rule->rounding_minutes" class="col-md-4 mb-3" required />
                <x-input name="max_daily_minutes" type="number" label="Daily maximum (minutes, 0 = no limit)" :value="$rule->max_daily_minutes" class="col-md-4 mb-3" required />
                <x-select name="rate_base" label="Rate Based On" :options="['basic' => 'Basic Salary', 'gross' => 'Gross Salary']" :value="$rule->rate_base" class="col-md-6 mb-3" required />
                <x-input name="monthly_hours_divisor" type="number" label="Monthly Hours Divisor" :value="$rule->monthly_hours_divisor" class="col-md-6 mb-3" required help="Hourly rate = salary / divisor. BD standard is 208." />
                <x-input name="workday_multiplier" type="number" step="0.01" label="Working Day Multiplier" :value="$rule->workday_multiplier" class="col-md-6 mb-3" required />
                <x-input name="offday_multiplier" type="number" step="0.01" label="Weekend / Holiday Multiplier" :value="$rule->offday_multiplier" class="col-md-6 mb-3" required />
            </div>
            <x-checkbox name="is_enabled" label="Enabled" :checked="$rule->is_enabled" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.overtime-rules.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
