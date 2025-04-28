<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProductController;

Route::apiResource('products', ProductController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', 'permission:create products'])->group(function () {
   Route::apiResource('products', ProductController::class)->except(['index', 'show']); // don't include index and show routes
});

include_once __DIR__ . '/auth.php';
