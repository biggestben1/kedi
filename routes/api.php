<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\ContactController as ApiContactController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DriverOrderController;
use App\Http\Controllers\Api\BonusController;
use App\Http\Controllers\Api\DpbvController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\KdInfoController as ApiKdInfoController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public storage (CORS-friendly for Flutter web)
    Route::get('storage/{path}', [StorageController::class, 'show'])
        ->where('path', '.*')
        ->name('api.storage');

    // Public
    Route::post('login', [ApiAuthController::class, 'login']);
    Route::post('register', [ApiAuthController::class, 'register']);
    Route::post('contact', [ApiContactController::class, 'store']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [ApiAuthController::class, 'logout']);
        Route::get('user', [ApiAuthController::class, 'user']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);

        Route::post('kd-info/search', [ApiKdInfoController::class, 'search']);
        Route::post('kd-info/auto-generate', [ApiKdInfoController::class, 'autoGenerate']);

        Route::get('wallet', [WalletController::class, 'balance']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/topup', [WalletController::class, 'topUp']);

        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);

        Route::get('bonuses', [BonusController::class, 'index']);
        Route::get('promos', [PromoController::class, 'index']);
        Route::get('dpbv', [DpbvController::class, 'index']);

        // Driver/Dispatch API (restricted to dispatch role)
        Route::get('driver/orders', [DriverOrderController::class, 'index']);
        Route::get('driver/orders/{order}', [DriverOrderController::class, 'show']);
        Route::patch('driver/orders/{order}/status', [DriverOrderController::class, 'updateStatus']);
        Route::patch('driver/orders/{order}/tracking', [DriverOrderController::class, 'updateTracking']);
        Route::patch('driver/orders/{order}/shipping-cost', [DriverOrderController::class, 'updateShippingCost']);
    });
});
