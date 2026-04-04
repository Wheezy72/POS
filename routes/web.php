<?php

declare(strict_types=1);

use App\Http\Controllers\POSApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/pos')->group(function (): void {
    Route::post('/search', [POSApiController::class, 'search']);
    Route::post('/checkout', [POSApiController::class, 'checkout']);
    Route::post('/void-sale', [POSApiController::class, 'voidSale'])
        ->middleware('RequireManagerPin');
});
