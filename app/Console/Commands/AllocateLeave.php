<?php

namespace App\Console\Commands;

use App\Services\LeaveService;
use Illuminate\Console\Command;

class AllocateLeave extends Command
{
    protected $signature = 'leave:allocate {year? : Defaults to the current year}';

    protected $description = 'Create yearly leave balances with carry forward';

    public function handle(LeaveService $service): int
    {
        $count = $service->allocateYear((int) ($this->argument('year') ?: now()->year));
        $this->info("Allocated {$count} leave balances.");

        return self::SUCCESS;
    }
}
