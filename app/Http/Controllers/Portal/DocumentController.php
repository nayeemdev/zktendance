<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.documents', [
            'documents' => $request->user()->employee->documents()->where('visible_to_employee', true)->latest()->get(),
        ]);
    }

    public function download(Request $request, EmployeeDocument $document, DocumentService $service)
    {
        abort_unless($document->employee_id === $request->user()->employee->id && $document->visible_to_employee, 403);

        return $service->download($document);
    }
}
