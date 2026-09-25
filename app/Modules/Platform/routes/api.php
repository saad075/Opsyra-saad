<?php

use App\Modules\Platform\Http\Controllers\Internal\TenantController;
use App\Modules\Platform\Http\Middleware\VerifyCentralSignature;
use Illuminate\Support\Facades\Route;

// Server-to-server only: Opsyra Central, signed with the shared secret.
Route::prefix('internal')->middleware([VerifyCentralSignature::class, 'throttle:60,1'])->group(function () {
    Route::post('tenants', [TenantController::class, 'store']);
});
