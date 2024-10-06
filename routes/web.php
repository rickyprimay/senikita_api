<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hidden-login', function () {
    return response()->json([
        'message' => 'Unauthorized',
        'code' => 401
    ], 401);
})->name('login');
