<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/media/{path}', MediaController::class)->where('path', '.*')->name('media.show');
Route::get('/san-pham', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/san-pham/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/gio-hang', [CartController::class, 'show'])->name('cart.show');
Route::post('/gio-hang/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/gio-hang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/quen-mat-khau', [PasswordController::class, 'forgot'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordController::class, 'email'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::get('/xac-minh-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/xac-minh-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/xac-minh-email/gui-lai', [VerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/thanh-toan', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
        Route::get('/thanh-toan/thanh-cong/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
        Route::post('/san-pham/{product}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
        Route::patch('/danh-gia/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account.index');
        Route::patch('/tai-khoan', [AccountController::class, 'update'])->name('account.update');
        Route::get('/tai-khoan/don-hang/{order}', [AccountController::class, 'order'])->name('account.order');
        Route::post('/tai-khoan/don-hang/{order}/huy', [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
        Route::post('/tai-khoan/yeu-thich/{product}', [AccountController::class, 'toggleWishlist'])->name('account.wishlist');
        Route::post('/tai-khoan/the', [AccountController::class, 'storeCard'])->name('account.cards.store')->middleware('throttle:5,1');
        Route::patch('/tai-khoan/the/{card}', [AccountController::class, 'updateCard'])->name('account.cards.update');
        Route::delete('/tai-khoan/the/{card}', [AccountController::class, 'deleteCard'])->name('account.cards.destroy');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:staff,admin'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/san-pham', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/san-pham', [AdminProductController::class, 'store'])->name('products.store');
    Route::patch('/san-pham/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/san-pham/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/danh-muc', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/danh-muc/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/danh-muc/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/don-hang', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/don-hang/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::get('/danh-gia', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/danh-gia/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
    Route::get('/thanh-toan', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('/thanh-toan/{payment}/hoan-tien', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/nguoi-dung', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/nguoi-dung/{user}', [AdminUserController::class, 'update'])->name('users.update');
    });
});
