<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogFilterRequest;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogController extends Controller
{
    public function index(AuditLogFilterRequest $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('auditable_type', 'App\\Models\\'.$type))
            ->when($request->event, fn ($q, $event) => $q->where('event', $event))
            ->when($request->from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        $types = AuditLog::distinct()->pluck('auditable_type')
            ->mapWithKeys(fn ($type) => [class_basename($type) => str(class_basename($type))->headline()->toString()])
            ->sort();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'types' => $types,
            'users' => User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])->pluck('name', 'id'),
        ]);
    }
}
