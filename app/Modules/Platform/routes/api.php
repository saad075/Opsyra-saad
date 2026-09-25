<?php

use App\Modules\Platform\Http\Controllers\Internal\SubscriptionController;
use App\Modules\Platform\Http\Controllers\Internal\TenantController;
use App\Modules\Platform\Http\Middleware\VerifyCentralSignature;
use Illuminate\Support\Facades\Route;

// Server-to-server only: Opsyra Central, signed with the shared secret.
Route::prefix('internal')->middleware([VerifyCentralSignature::class, 'throttle:120,1'])->group(function () {
    Route::post('tenants', [TenantController::class, 'store']);
    Route::post('tenants/{clientCode}/subscription', [SubscriptionController::class, 'update']);
    Route::post('usage', [SubscriptionController::class, 'usage']);
});
