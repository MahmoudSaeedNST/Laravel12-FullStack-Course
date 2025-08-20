<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderManagementController;
use App\Http\Controllers\Api\PaymentController;

Route::apiResource('products', ProductController::class)->only(['index', 'show']);

// Product filtering endpoint
Route::get('/products/filter', [ProductController::class, 'filter']);

Route::middleware(['auth:sanctum', 'permission:create products'])->group(function () {
   Route::apiResource('products', ProductController::class)->except(['index', 'show']); // don't include index and show routes
   
   // Admin specific product routes
   Route::get('/products/admin', [ProductController::class, 'adminIndex']);
   Route::post('/products/{product}/restore', [ProductController::class, 'undoDelete']);
   Route::delete('/products/{product}/permanent', [ProductController::class, 'permanentDelete']);
});
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', 'permission:create categories'])->group(function () {
   Route::apiResource('categories', CategoryController::class)->except(['index', 'show']); // don't include index and show routes
});

Route::middleware(['auth:sanctum', 'permission:create orders'])->group(function () {
   Route::apiResource('cart', CartController::class)->except(['show']); // don't include index and show routes
});


Route::middleware(['auth:sanctum', 'permission:create orders'])->group(function () {

   // handle orders
   Route::post('/checkout', [CheckoutController::class, 'checkout']);
   Route::get('/orders', [CheckoutController::class, 'orderHistory']);
   Route::get('/orders/{id}', [CheckoutController::class, 'orderDetails']);

   // handle payment
   // Create payment (Stripe or other providers in the future)
   Route::post('/orders/{order}/payments', [PaymentController::class, 'createPayment']);

   // Confirm payment status
   Route::get('/payments/{paymentId}/confirm', [PaymentController::class, 'confirmPayment']);
});

// Webhook endpoints (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);

Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

// Admin-only order management routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Order management endpoints
    Route::get('/admin/orders', [OrderManagementController::class, 'index']);
    Route::get('/admin/orders/{order}', [OrderManagementController::class, 'show']);
    Route::patch('/admin/orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
    Route::post('/admin/orders/{order}/cancel', [OrderManagementController::class, 'cancel']);
});


include_once __DIR__ . '/auth.php';
