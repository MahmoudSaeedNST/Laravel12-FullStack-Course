<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\PaymentController;

Route::apiResource('products', ProductController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', 'permission:create products'])->group(function () {
   Route::apiResource('products', ProductController::class)->except(['index', 'show']); // don't include index and show routes
});
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', 'permission:create categories'])->group(function () {
   Route::apiResource('categories', ProductController::class)->except(['index', 'show']); // don't include index and show routes
});

Route::middleware(['auth:sanctum', 'permission:create orders'])->group(function () {
   Route::apiResource('cart', CartController::class)->except(['show']); // don't include index and show routes
});


Route::middleware(['auth:sanctum', 'permission:create orders'])->group(function () {

   // handle orders
   Route::post('/checkout', [CheckoutController::class, 'checkout']);
   Route::get('/orders', [CheckoutController::class, 'orderHistory']);
   Route::get('/orders/{orderId}', [CheckoutController::class, 'orderDetails']);

   // handle payment
   // Create payment (Stripe or other providers in the future)
   Route::post('/orders/{order}/payments', [PaymentController::class, 'createPayment']);

   // Confirm payment status
   Route::get('/payments/{paymentId}/confirm', [PaymentController::class, 'confirmPayment']);
});

// Webhook endpoints (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);



Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

include_once __DIR__ . '/auth.php';
