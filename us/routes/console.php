<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// HR module (App\Modules\Hr\Console) — same times as SLMS360's schedule.
// "Today" is the app timezone (UTC) for every tenant; see docs/status.md.
Schedule::command('staff:activate-pending')->dailyAt('00:05')->timezone('UTC');
Schedule::command('staff:process-offboarding')->dailyAt('00:05')->timezone('UTC');

// Settles finished attendance days; hourly because "finished" depends on
// each branch's timezone and on night shifts. Each day is settled once.
Schedule::command('attendance:close-out')->hourlyAt(10)->withoutOverlapping();
Schedule::command('visits:generate')->dailyAt('01:30')->timezone('UTC');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The public demo company is rebuilt from scratch every night.
Schedule::command('demo:reset')->dailyAt('03:00')->timezone('UTC');
