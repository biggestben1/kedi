<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DriverOrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('login', [ApiAuthController::class, 'login']);
    Route::post('register', [ApiAuthController::class, 'register']);

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

        Route::get('wallet', [WalletController::class, 'balance']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/topup', [WalletController::class, 'topUp']);

        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);

        // Driver/Dispatch API (restricted to dispatch role)
        Route::get('driver/orders', [DriverOrderController::class, 'index']);
        Route::get('driver/orders/{order}', [DriverOrderController::class, 'show']);
        Route::patch('driver/orders/{order}/status', [DriverOrderController::class, 'updateStatus']);
        Route::patch('driver/orders/{order}/tracking', [DriverOrderController::class, 'updateTracking']);
    });
});
