<?php

use App\Http\Controllers\Admin\AdminAuthController;
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
// Placeholder until the live demo is built.
Route::view('/demo', 'coming-soon', ['title' => 'Demo', 'heading' => 'Explore the live demo', 'text' => 'A sample company with staff, approvals, checklists and payslips, reset every night. Being built now.'])->name('demo');

// BrainStormers super-admin area.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'show'])->middleware('guest')->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:20,1']);

    Route::middleware(['auth', EnsureActiveAdmin::class])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::redirect('/', '/admin/pricing')->name('home');
        Route::get('pricing/{country?}', [PricingController::class, 'edit'])->name('pricing');
        Route::post('pricing/{country}', [PricingController::class, 'update'])->name('pricing.update');
        Route::post('pricing/{country}/publish', [PricingController::class, 'publish'])->name('pricing.publish');
        Route::get('plans', [PlanController::class, 'index'])->name('plans');
        Route::post('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    });
});
