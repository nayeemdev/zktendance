<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    private const DISK = 'local';

    public function upload(Employee $employee, UploadedFile $file, array $data, User $user): EmployeeDocument
    {
        return $employee->documents()->create([
            'title' => $data['title'],
            'path' => $file->store("documents/{$employee->id}", self::DISK),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'expires_on' => $data['expires_on'] ?? null,
            'visible_to_employee' => (bool) ($data['visible_to_employee'] ?? false),
            'uploaded_by' => $user->id,
        ]);
    }

    public function download(EmployeeDocument $document)
    {
        return Storage::disk(self::DISK)->download($document->path, $document->original_name);
    }

    public function delete(EmployeeDocument $document): void
    {
        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();
    }
}
