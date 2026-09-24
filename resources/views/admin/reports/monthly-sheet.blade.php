@extends('layouts.app')

@section('title', 'Attendance Sheet '.$month->format('F Y'))

@section('content')
@include('admin.reports.filters')

@php($codes = ['present' => ['P', 'success'], 'late' => ['L', 'warning'], 'half_day' => ['HD', 'info'], 'absent' => ['A', 'danger'], 'leave' => ['LV', 'primary'], 'unpaid_leave' => ['UL', 'secondary'], 'holiday' => ['H', 'dark'], 'weekend' => ['W', 'muted']])
<p class="small">
    @foreach ($codes as $status => [$code, $color])
        <span class="me-2"><strong class="text-{{ $color }}">{{ $code }}</strong> {{ \App\Models\Attendance::STATUSES[$status] }}</span>
    @endforeach
</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered sheet mb-0">
            <thead>
            <tr>
                <th class="text-start">Employee</th>
                @for ($d = 1; $d <= $days; $d++)
                    <th>{{ $d }}<div class="text-muted fw-normal">{{ $month->copy()->day($d)->format('D')[0] }}</div></th>
                @endfor
            </tr>
            </thead>
            <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td class="text-start text-nowrap">{{ $employee->name }}</td>
                    @for ($d = 1; $d <= $days; $d++)
                        @php($row = $attendances[$employee->id][$d] ?? null)
                        <td @if($row && $row->check_in) title="{{ $row->check_in->format('h:i A') }} - {{ $row->check_out?->format('h:i A') ?? '?' }}" @endif>
                            @if ($row)
                                <strong class="text-{{ $codes[$row->status][1] }}">{{ $codes[$row->status][0] }}</strong>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
