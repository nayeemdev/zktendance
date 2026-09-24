<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessAttendance extends Command
{
    protected $signature = 'attendance:process {from? : Y-m-d, defaults to yesterday} {to? : Y-m-d, defaults to today}';

    protected $description = 'Build daily attendance from raw device punches';

    public function handle(AttendanceService $service): int
    {
        $from = $this->argument('from') ? Carbon::parse($this->argument('from')) : today()->subDay();
        $to = $this->argument('to') ? Carbon::parse($this->argument('to')) : today();

        $count = $service->processRange($from, $to);
        $this->info("Processed {$count} attendance records.");

        return self::SUCCESS;
    }
}
