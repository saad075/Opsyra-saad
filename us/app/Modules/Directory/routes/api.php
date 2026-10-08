<?php

use App\Modules\Directory\Http\Controllers\ResolveClientController;
use Illuminate\Support\Facades\Route;

// Unauthenticated by design — this is the one hop before a tenant is known.
Route::post('/resolve', ResolveClientController::class);
