<?php

namespace Tests\Feature;

use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 12:00:00');
        $this->setUpCompany();
    }

    private function readXlsx(string $content): array
    {
        $path = storage_path('app/tmp/test-'.uniqid().'.xlsx');
        file_put_contents($path, $content);
        $reader = new Reader(new Options(tempFolder: storage_path('app/tmp')));
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();
        unlink($path);

        return $rows;
    }

    public function test_reports_export_to_excel_and_csv(): void
    {
        $this->makeEmployee(['name' => 'Excel Person']);

        foreach (['admin.reports.monthly-summary', 'admin.reports.monthly-sheet', 'admin.reports.late'] as $route) {
            $response = $this->actingAs($this->admin)->get(route($route, ['export' => 'xlsx']));
            $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            unlink($response->getFile()->getPathname());

            $this->actingAs($this->admin)->get(route($route, ['export' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }

        $response = $this->actingAs($this->admin)->get(route('admin.reports.monthly-summary', ['export' => 'xlsx', 'month' => '2026-09']));
        $rows = $this->readXlsx(file_get_contents($response->getFile()->getPathname()));
        unlink($response->getFile()->getPathname());
        $this->assertSame('Code', $rows[0][0]);
        $this->assertSame('Excel Person', $rows[1][1]);
    }

    public function test_payroll_exports_to_excel(): void
    {
        $this->makeEmployee([], 30000);
        $run = app(PayrollService::class)->create('2026-08', null, $this->admin);

        $response = $this->actingAs($this->admin)->get(route('admin.payroll.export', [$run, 'format' => 'xlsx']));
        $response->assertOk();
        $rows = $this->readXlsx(file_get_contents($response->getFile()->getPathname()));
        unlink($response->getFile()->getPathname());
        $this->assertCount(2, $rows);
        $this->assertSame('Net Pay', $rows[0][11]);
    }
}
