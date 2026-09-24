@extends('layouts.app')

@section('title', 'Leave Balances')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-2"><input type="number" name="year" value="{{ $year }}" class="form-control"></div>
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <div class="col-md-2"><button class="btn btn-secondary w-100">Show</button></div>
    </div>
</form>

<x-page-header subtitle="Remaining / total days. Balances are created every 1 January and carry forward unused days where allowed.">
    <x-post-button :action="route('admin.leave-balances.allocate')" :label="'Allocate '.$year.' balances'" icon="refresh">
        <input type="hidden" name="year" value="{{ $year }}">
    </x-post-button>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>Employee</th>
                @foreach ($leaveTypes as $type)
                    <th class="text-center">{{ $type->code }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_code }} - {{ $employee->name }}</td>
                    @foreach ($leaveTypes as $type)
                        @php($balance = $balances[$employee->id][$type->id] ?? null)
                        <td class="text-center">
                            @if ($balance)
                                <a href="#" data-bs-toggle="modal" data-bs-target="#balance{{ $balance->id }}">{{ $balance->remaining() }} / {{ $balance->allocated + $balance->carried_forward }}</a>
                                <div class="modal fade" id="balance{{ $balance->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <form method="POST" action="{{ route('admin.leave-balances.update', $balance) }}" class="modal-content text-start">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header"><h6 class="modal-title">{{ $employee->name }}, {{ $type->name }}</h6></div>
                                            <div class="modal-body">
                                                <x-input name="allocated" type="number" step="0.5" label="Allocated" :value="$balance->allocated" :id="'a'.$balance->id" />
                                                <x-input name="carried_forward" type="number" step="0.5" label="Carried Forward" :value="$balance->carried_forward" :id="'c'.$balance->id" />
                                                <div class="small text-muted">Used: {{ $balance->used }} &middot; Encashed: {{ $balance->encashed }}</div>
                                            </div>
                                            <div class="modal-footer"><button class="btn btn-primary btn-sm">Save</button></div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
