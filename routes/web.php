<?php

use App\Http\Controllers\WebControllers\AuthController;
use App\Http\Controllers\WebControllers\ContentsController;
use App\Http\Controllers\WebControllers\VerificationCodeController;
use App\Http\Controllers\WebControllers\PaymentController;
use Illuminate\Support\Facades\Route;




Route::get('/me', [AuthController::class, 'me']);
Route::get('/coupons', [ContentsController::class, 'coupons']);

Route::get('/{any}', function () {
    return response()->file(public_path('index.html'));
})->where('any', '.*');

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::post('/update-password', [AuthController::class, 'updatePassword']);
Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

Route::post('/verify-otp', [VerificationCodeController::class, 'verifyOtp']);
Route::post('/sendOtp', [VerificationCodeController::class, 'sendOtp']);

Route::post('/createIvdCoupan', [ContentsController::class, 'createIvdCoupan']);
Route::post('/create-order', [PaymentController::class, 'createOrder']);

Route::post('/verifyPayment', [PaymentController::class, 'verifyPayment']);
