<?php

use App\Http\Controllers\Api\ResolveCompanyController;
use Illuminate\Support\Facades\Route;

// Public, for the staff mobile app: company code → its country server.
Route::post('resolve', ResolveCompanyController::class)->middleware('throttle:30,1');
