<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::resource('/categories', CategoryController::class)->only(['index', 'show','store', 'update', 'destroy']);
        Route::get('/categories/search', [CategoryController::class, 'search']);
        
        //TODO: Add routes for wallets, invoices and user profile


        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
