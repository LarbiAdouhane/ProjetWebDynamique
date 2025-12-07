<?php


use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);

Route::get('/', [App\Http\Controllers\Mycontroller::class, 'index']);


use App\Http\Controllers\Api\PayPalController;

Route::get('/payment/success', [PayPalController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [PayPalController::class, 'cancel'])->name('payment.cancel');
