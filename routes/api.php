<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\Shop\ImageProductController;
use App\Http\Controllers\Api\User\Shop\ProductController;
use App\Http\Controllers\Api\User\ShopController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('resend-otp', [AuthController::class, 'resendOTP']);
    Route::middleware('auth:api')->post('logout', [AuthController::class, 'logout']);
    Route::middleware('auth:api')->post('refresh', [AuthController::class, 'refreshToken']);
});

Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {

    // User Management
    Route::get('users', [UserController::class, 'getAllUsers']);
    Route::get('users/{id}', [UserController::class, 'getUser']);
    Route::post('users', [UserController::class, 'createUser']);
    Route::put('users/{id}', [UserController::class, 'updateUser']); 
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);

    // Category Management
    Route::get('category', [CategoryController::class, 'index']);
    Route::get('category/{id}', [CategoryController::class, 'show']);
    Route::post('category', [CategoryController::class, 'store']);
    Route::put('category/{id}', [CategoryController::class, 'update']);
    Route::delete('category/{id}', [CategoryController::class, 'destroy']);

});

Route::prefix('user')->middleware(['auth:api', 'user'])->group(function () {

    // Shop
    Route::post('shop', [ShopController::class, 'create']);
    Route::put('shop/{id}', [ShopController::class, 'update']);

    // Product Shop
    Route::get('/shop/products', [ProductController::class, 'index']);
    Route::get('/shop/products/{id}', [ProductController::class, 'show']);
    Route::post('/shop/products', [ProductController::class, 'create']);
    Route::put('/shop/products/{id}', [ProductController::class, 'update']);
    Route::delete('/shop/products/{id}', [ProductController::class, 'destroy']);

    // Product Image
    Route::post('/shop/products/{id}/image', [ImageProductController::class, 'create']);

});