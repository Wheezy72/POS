<?php

declare(strict_types=1);

use App\Http\Controllers\POSApiController;
use App\Http\Controllers\SecurityApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/pos')->group(function (): void {
    Route::post('/search', [POSApiController::class, 'search']);
    Route::post('/checkout', [POSApiController::class, 'checkout']);
    Route::post('/void-sale', [POSApiController::class, 'voidSale'])
        ->middleware('RequireManagerPin');
});

Route::prefix('api/auth')->group(function (): void {
    Route::post('/pin-login', [SecurityApiController::class, 'pinLogin']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/api/auth/manager-override', [SecurityApiController::class, 'managerOverride']);
    Route::post('/api/shifts/open', [SecurityApiController::class, 'openShift']);
    Route::post('/api/shifts/close', [SecurityApiController::class, 'closeShift']);
});
