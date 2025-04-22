<?php

use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\CustomerAuthController;
use App\Http\Controllers\Api\Auth\DeliveryAuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function(){
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    
    Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::get('/token', [AdminAuthController::class, 'getAccessToken']);
    });
});

Route::prefix('customer')->group(function(){
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);
    
    Route::middleware(['auth:sanctum', 'isCustomer'])->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/me', [CustomerAuthController::class, 'me']);
        Route::get('/token', [CustomerAuthController::class, 'getAccessToken']);
    });
});

Route::prefix('delivery')->group(function(){
    Route::post('/register', [DeliveryAuthController::class, 'register']);
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    
    Route::middleware(['auth:sanctum', 'isDelivery'])->group(function () {
        Route::post('/logout', [DeliveryAuthController::class, 'logout']);
        Route::get('/me', [DeliveryAuthController::class, 'me']);
        Route::get('/token', [DeliveryAuthController::class, 'getAccessToken']);
    });
});