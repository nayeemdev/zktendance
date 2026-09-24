@extends('layouts.app')

@section('title', $salaryComponent->exists ? 'Edit Component' : 'Add Component')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ $salaryComponent->exists ? route('admin.salary-components.update', $salaryComponent) : route('admin.salary-components.store') }}">
            @csrf
            @if ($salaryComponent->exists) @method('PUT') @endif
            <x-input name="name" label="Name" :value="$salaryComponent->name" required />
            <div class="row">
                <x-select name="type" label="Type" :options="['earning' => 'Earning', 'deduction' => 'Deduction']" :value="$salaryComponent->type" class="col-md-6 mb-3" required />
                <x-input name="sort_order" type="number" label="Display Order" :value="$salaryComponent->sort_order" class="col-md-6 mb-3" />
            </div>
            <x-checkbox name="is_basic" label="This is the Basic salary" :checked="$salaryComponent->is_basic" help="Used for % of Basic, overtime rate and deductions." />
            <x-checkbox name="is_taxable" label="Taxable income" :checked="$salaryComponent->is_taxable" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.salary-components.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
