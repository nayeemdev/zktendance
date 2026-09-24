<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\DocumentService;

class EmployeeDocumentController extends Controller
{
    public function __construct(private DocumentService $service) {}

    public function index()
    {
        return view('admin.documents', [
            'documents' => EmployeeDocument::with('employee')
                ->whereNotNull('expires_on')
                ->whereDate('expires_on', '<=', today()->addDays(30))
                ->orderBy('expires_on')
                ->get(),
        ]);
    }

    public function store(EmployeeDocumentRequest $request, Employee $employee)
    {
        $this->service->upload($employee, $request->file('file'), $request->validated(), $request->user());

        return back()->with('success', 'Document uploaded.');
    }

    public function download(EmployeeDocument $document)
    {
        return $this->service->download($document);
    }

    public function destroy(EmployeeDocument $document)
    {
        $this->service->delete($document);

        return back()->with('success', 'Document deleted.');
    }
}
