<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// HR module (App\Modules\Hr\Console) — same times as SLMS360's schedule.
// "Today" is the app timezone (UTC) for every tenant; see docs/status.md.
Schedule::command('staff:activate-pending')->dailyAt('00:05')->timezone('UTC');
Schedule::command('staff:process-offboarding')->dailyAt('00:05')->timezone('UTC');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
