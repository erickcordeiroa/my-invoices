<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        /** Categories */
        Route::get('/categories/search', [CategoryController::class, 'search']);
        Route::resource('/categories', CategoryController::class)->only(['index', 'show','store', 'update', 'destroy']);

        /** Wallets */
        Route::get('/wallets/search', [WalletController::class, 'search']);
        Route::resource('/wallets', WalletController::class)->only(['index', 'show','store', 'update', 'destroy']);

        /** Invoices */
        Route::get('/invoices/search', [InvoiceController::class, 'search']);
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
        Route::post('/invoices/{invoice}/unpay', [InvoiceController::class, 'unpay']);
        Route::resource('/invoices', InvoiceController::class)->only(['index', 'show','store', 'update', 'destroy']);

        //TODO: Add routes for user profile

        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
