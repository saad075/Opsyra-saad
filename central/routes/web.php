<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\CompanyLoginController;
use App\Http\Controllers\GetStartedController;
use App\Http\Controllers\PublicPricingController;
use App\Http\Middleware\EnsureActiveAdmin;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/pricing', PublicPricingController::class)->name('pricing');

Route::get('/login', [CompanyLoginController::class, 'show'])->name('login');
Route::post('/login', [CompanyLoginController::class, 'submit'])->middleware('throttle:30,1');
Route::get('/get-started', [GetStartedController::class, 'show'])->name('get-started');
Route::post('/get-started', [GetStartedController::class, 'store'])->middleware('throttle:10,1');
Route::get('/get-started/check-code', [GetStartedController::class, 'checkCode'])->middleware('throttle:60,1')->name('get-started.check-code');
Route::get('/get-started/done', [GetStartedController::class, 'done'])->name('get-started.done');
// The live demo company lives on the US deployment and is rebuilt nightly
// there (demo:reset). Its sign-in details are public by design.
Route::get('/demo', function () {
    $appUrl = config('opsyra.countries.'.config('opsyra.demo.country').'.url');

    return view('demo', [
        'appUrl' => $appUrl ? rtrim($appUrl, '/').'/login?'.http_build_query(['client_code' => 'demo', 'demo' => 1]) : null,
        'email' => config('opsyra.demo.email'),
        'password' => config('opsyra.demo.password'),
    ]);
})->name('demo');

// BrainStormers super-admin area.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'show'])->middleware('guest')->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:20,1']);

    Route::middleware(['auth', EnsureActiveAdmin::class])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('home');
        Route::get('companies', [CompanyController::class, 'index'])->name('companies');
        Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::post('companies/{company}/extend-trial', [CompanyController::class, 'extendTrial'])->name('companies.extend-trial');
        Route::post('companies/{company}/plan', [CompanyController::class, 'changePlan'])->name('companies.plan');
        Route::post('companies/{company}/status', [CompanyController::class, 'setStatus'])->name('companies.status');
        Route::post('companies/{company}/retry', [CompanyController::class, 'retry'])->name('companies.retry');
        Route::post('companies/{company}/resend-welcome', [CompanyController::class, 'resendWelcome'])->name('companies.resend-welcome');
        Route::get('pricing/{country?}', [PricingController::class, 'edit'])->name('pricing');
        Route::post('pricing/{country}', [PricingController::class, 'update'])->name('pricing.update');
        Route::post('pricing/{country}/publish', [PricingController::class, 'publish'])->name('pricing.publish');
        Route::get('plans', [PlanController::class, 'index'])->name('plans');
        Route::post('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    });
});
