<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/send-otp', [OtpController::class, 'showSend'])->name('otp.send');
Route::post('/send-otp', [OtpController::class, 'send'])->middleware('throttle:5,1')->name('otp.send.store');
Route::get('/verify-otp', [OtpController::class, 'showVerify'])->name('otp.verify');
Route::post('/verify-otp', [OtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify.store');
Route::get('/otp-success', [OtpController::class, 'success'])->name('otp.success');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/verify-email', [AuthController::class, 'showVerification'])->name('verification.notice');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.resend');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/my-orders', [OrderController::class, 'index'])->middleware('auth')->name('orders.index');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment', [AdminController::class, 'verifyPayment'])->name('orders.payment');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::post('/products', [AdminController::class, 'saveProduct'])->name('products.save');
    Route::delete('/products/{product}', [AdminController::class, 'deleteProduct'])->name('products.delete');
    Route::get('/customers', [AdminController::class, 'customers'])->name('customers');
    Route::patch('/customers/{user}', [AdminController::class, 'updateCustomer'])->name('customers.update');
    Route::delete('/customers/{user}', [AdminController::class, 'deleteCustomer'])->name('customers.delete');
    Route::get('/staff', [AdminController::class, 'staff'])->name('staff');
    Route::post('/staff', [AdminController::class, 'createStaff'])->name('staff.create');
    Route::patch('/staff/{user}/password', [AdminController::class, 'resetStaffPassword'])->name('staff.password');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings/profile', [AdminController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/categories', [AdminController::class, 'createCategory'])->name('settings.categories.create');
    Route::delete('/settings/categories/{category}', [AdminController::class, 'deleteCategory'])->name('settings.categories.delete');
});
