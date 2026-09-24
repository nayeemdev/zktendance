@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2">
        <x-select name="user_id" :options="$users" :value="request('user_id')" placeholder="Any user" class="col-md-2" />
        <x-select name="type" :options="$types" :value="request('type')" placeholder="Any record" class="col-md-3" />
        <x-select name="event" :options="['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'login' => 'Login']" :value="request('event')" placeholder="Any action" class="col-md-2" />
        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-1"><button class="btn btn-secondary w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record</th><th>Changes</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="text-nowrap small">{{ $log->created_at->format('d M Y h:i A') }}<div class="text-muted">{{ $log->ip_address }}</div></td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td><span class="badge text-bg-{{ ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger', 'login' => 'secondary'][$log->event] ?? 'secondary' }}">{{ ucfirst($log->event) }}</span></td>
                    <td>{{ $log->subjectName() }}<div class="small text-muted">{{ $log->description }}</div></td>
                    <td class="small">
                        @if ($log->event === 'updated')
                            @foreach ($log->new_values ?? [] as $field => $value)
                                <div><strong>{{ str($field)->headline() }}:</strong> <span class="text-danger text-decoration-line-through">{{ is_array($log->old_values[$field] ?? null) ? json_encode($log->old_values[$field]) : \Illuminate\Support\Str::limit((string) ($log->old_values[$field] ?? ''), 60) }}</span> <i class="hgi-stroke hgi-arrow-right-02"></i> <span class="text-success">{{ is_array($value) ? json_encode($value) : \Illuminate\Support\Str::limit((string) $value, 60) }}</span></div>
                            @endforeach
                        @elseif ($log->event !== 'login')
                            <details>
                                <summary class="text-muted">{{ count($log->new_values ?? $log->old_values ?? []) }} fields</summary>
                                @foreach (($log->new_values ?? $log->old_values ?? []) as $field => $value)
                                    <div><strong>{{ str($field)->headline() }}:</strong> {{ is_array($value) ? json_encode($value) : \Illuminate\Support\Str::limit((string) $value, 60) }}</div>
                                @endforeach
                            </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No activity recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
