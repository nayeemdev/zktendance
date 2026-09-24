<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('zk:sync')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('attendance:process')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('leave:allocate')->monthlyOn(1, '00:10');
Schedule::command('devices:check')->everyTenMinutes()->withoutOverlapping();
