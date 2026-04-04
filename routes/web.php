<?php

declare(strict_types=1);

use App\Http\Controllers\AdminApiController;
use App\Http\Controllers\PaymentApiController;
use App\Http\Controllers\POSApiController;
use App\Http\Controllers\SecurityApiController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::view('/pos', 'pos-checkout');

Route::middleware(['auth', 'role:cashier'])->prefix('api/pos')->group(function (): void {
    Route::post('/search', [POSApiController::class, 'search']);
    Route::post('/checkout', [POSApiController::class, 'checkout']);
    Route::get('/mpesa/live-feed', [PaymentApiController::class, 'liveFeed']);
    Route::post('/mpesa/stk-push', [PaymentApiController::class, 'stkPush']);
    Route::post('/mpesa/stk-status', [PaymentApiController::class, 'stkStatus']);
    Route::post('/mpesa-verify', [POSApiController::class, 'verifyMpesaTransaction']);
    Route::post('/void-sale', [POSApiController::class, 'voidSale'])
        ->middleware('RequireManagerPin');
});

Route::post('/api/webhooks/mpesa/c2b', [PaymentApiController::class, 'receiveC2bWebhook']);
Route::post('/api/login-pin', [SecurityApiController::class, 'posPinLogin'])
    ->middleware('throttle:pin-login');

Route::prefix('api/auth')->group(function (): void {
    Route::post('/pin-login', [SecurityApiController::class, 'pinLogin'])
        ->middleware('throttle:pin-login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/api/auth/manager-override', [SecurityApiController::class, 'managerOverride'])
        ->middleware('throttle:manager-override');
    Route::post('/api/shifts/open', [SecurityApiController::class, 'openShift']);
    Route::post('/api/shifts/close', [SecurityApiController::class, 'closeShift']);
});

Route::middleware(['auth', 'role:admin,manager'])->prefix('api/admin')->group(function (): void {
    Route::post('/products', [AdminApiController::class, 'storeProduct']);
    Route::put('/products/{id}', [AdminApiController::class, 'updateProduct']);
    Route::post('/cashiers', [AdminApiController::class, 'storeCashier']);
    Route::get('/reports/daily-summary', [AdminApiController::class, 'dailySummary']);
});

Route::middleware(['auth', 'role:admin'])->prefix('api/admin/reports')->group(function (): void {
    Route::get('/inventory-velocity', [AdminApiController::class, 'inventoryVelocity']);
    Route::get('/margin-bleed', [AdminApiController::class, 'marginBleed']);
    Route::get('/peak-hours', [AdminApiController::class, 'peakHours']);
});
