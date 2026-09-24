<?php

namespace Tests\Feature;

use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        Storage::fake('local');
        $this->setUpCompany();
    }

    public function test_upload_download_and_delete(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin)->post(route('admin.employees.documents.store', $employee), [
            'title' => 'Employment Contract',
            'file' => UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf'),
            'expires_on' => '2026-10-10',
            'visible_to_employee' => 1,
        ])->assertSessionHas('success');

        $document = EmployeeDocument::first();
        Storage::disk('local')->assertExists($document->path);
        $this->assertTrue($document->expiresSoon());

        $this->actingAs($this->admin)->get(route('admin.employees.show', $employee))->assertSee('Employment Contract');
        $this->actingAs($this->admin)->get(route('admin.documents.index'))->assertSee('Employment Contract');
        $this->actingAs($this->admin)->get(route('admin.documents.download', $document))->assertOk()->assertDownload('contract.pdf');

        $this->actingAs($this->admin)->delete(route('admin.documents.destroy', $document));
        Storage::disk('local')->assertMissing($document->path);
        $this->assertSame(0, EmployeeDocument::count());
    }

    public function test_employee_sees_only_own_shared_documents(): void
    {
        $employee = $this->makeEmployee();
        $other = $this->makeEmployee();
        $user = User::create(['name' => 'E', 'email' => 'e@example.com', 'password' => 'password', 'role' => 'employee']);
        $employee->update(['user_id' => $user->id]);

        $make = fn ($emp, $title, $visible) => $emp->documents()->create([
            'title' => $title, 'path' => UploadedFile::fake()->create('x.pdf')->store('documents', 'local'), 'original_name' => 'x.pdf', 'size' => 10, 'visible_to_employee' => $visible,
        ]);
        $shared = $make($employee, 'My NID', true);
        $hidden = $make($employee, 'HR Notes', false);
        $foreign = $make($other, 'Other CV', true);

        $this->actingAs($user)->get(route('portal.documents.index'))->assertSee('My NID')->assertDontSee('HR Notes')->assertDontSee('Other CV');
        $this->actingAs($user)->get(route('portal.documents.download', $shared))->assertOk();
        $this->actingAs($user)->get(route('portal.documents.download', $hidden))->assertForbidden();
        $this->actingAs($user)->get(route('portal.documents.download', $foreign))->assertForbidden();
    }

    public function test_rejects_unsafe_files(): void
    {
        $this->actingAs($this->admin)->post(route('admin.employees.documents.store', $this->makeEmployee()), [
            'title' => 'Script',
            'file' => UploadedFile::fake()->create('run.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('file');
    }
}
