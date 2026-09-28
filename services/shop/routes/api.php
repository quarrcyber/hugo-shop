<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product:slug}', [ProductController::class, 'show']);
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/payments/webhook', PaymentWebhookController::class)->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/account', [AccountController::class, 'show']);
        Route::patch('/account', [AccountController::class, 'update']);
        Route::post('/cart/items/{product}', [CartController::class, 'add']);
        Route::patch('/cart/items/{product}', [CartController::class, 'update']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);

        Route::prefix('admin')->middleware('role:staff,admin')->group(function (): void {
            Route::get('/overview', [AdminController::class, 'overview']);
            Route::middleware('role:admin')->group(function (): void {
                Route::get('/users', [AdminController::class, 'users']);
                Route::patch('/users/{user}', [AdminController::class, 'updateUser']);
            });
        });
    });
});
