<?php

use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('resend-otp', [AuthController::class, 'resendOTP']);
Route::middleware('auth:api')->post('logout', [AuthController::class, 'logout']);

Route::get('users', [UserController::class, 'getAllUsers']);