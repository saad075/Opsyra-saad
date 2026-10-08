<?php

use Illuminate\Support\Facades\Schedule;

// Active staff counts from each country, for per-employee pricing and the
// subscription dashboard. Counts only — no employee data leaves a country.
Schedule::command('companies:sync-usage')->hourly();
