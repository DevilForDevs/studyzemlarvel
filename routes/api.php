<?php

use App\Http\Controllers\ApiControllers\TokenVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/verifyToken', [TokenVerificationController::class, 'verifyToken']);
Route::get('/coupons', [TokenVerificationController::class, 'getAllCoupons']);
