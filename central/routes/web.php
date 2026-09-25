<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\CompanyLoginController;
use App\Http\Controllers\PublicPricingController;
use App\Http\Middleware\EnsureActiveAdmin;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/pricing', PublicPricingController::class)->name('pricing');

// Placeholders until each flow is built (docs/decisions.md, "The product
// starts at the landing website"): free-trial sign-up and the live demo.
Route::get('/login', [CompanyLoginController::class, 'show'])->name('login');
Route::post('/login', [CompanyLoginController::class, 'submit'])->middleware('throttle:30,1');
Route::view('/get-started', 'coming-soon', ['title' => 'Get started', 'heading' => 'Start your free trial', 'text' => '14 days, every feature, no card. Sign-up is being built now.'])->name('get-started');
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
