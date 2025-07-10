# Module 4: Payment Integration - Part 2: Stripe Direct API Integration

## Introduction

we'll integrate Stripe payment gateway directly using their PHP SDK instead of Laravel Cashier. Direct API integration gives us more flexibility for one-time payments, allows us to keep our database structure clean, and provides greater control over the payment flow. Stripe's REST API is well-documented and their PHP SDK makes it easy to process payments securely in our Laravel e-commerce API.

## Let's Build: Direct Stripe API Integration

### Step 1: Install Stripe PHP Library

First, install the official Stripe PHP SDK:

```bash
composer require stripe/stripe-php
```

### Step 2: Create Payment Provider Enum

Let's start by creating an enum to define our supported payment providers:

```bash
php artisan make:enum Enum\PaymentProvider
```

```php
<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal'; // We'll implement this in Part 3
    
    /**
     * Get all payment providers as an array for validation
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get a human-readable label for the payment provider
     */
    public function label(): string
    {
        return match($this) {
            self::STRIPE => 'Credit Card (Stripe)',
            self::PAYPAL => 'PayPal',
        };
    }
}
```

### Step 3: Create Payment Model and Migration

Create a model to track payment attempts:

```bash
php artisan make:model Payment -m
```

Update the migration file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // Using PaymentProvider enum
            $table->string('session_id')->nullable(); // For Stripe session ID
            $table->string('payment_intent_id')->nullable(); // For Stripe payment intent ID
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status'); // Using PaymentStatus enum
            $table->json('metadata')->nullable(); // Additional payment data
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

Now update the Payment model:

```php
<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'session_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at'
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the order this payment belongs to
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark payment as completed
     * 
     * @param string $paymentIntentId The payment intent ID from Stripe
     * @param array $metadata Additional metadata to store
     * @return void
     */
    public function markAsCompleted(string $paymentIntentId, array $metadata = []): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_intent_id' => $paymentIntentId,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
        ]);

        // Also update the associated order
        $this->order->markAsPaid($paymentIntentId);
    }

    /**
     * Mark payment as failed
     * 
     * @param array $metadata Error details to store
     * @return void
     */
    public function markAsFailed(array $metadata = []): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);

        // Update order payment status
        $this->order->markPaymentFailed();
    }
    
    /**
     * Check if the payment is in a final state
     * 
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this->status, [
            PaymentStatus::COMPLETED, 
            PaymentStatus::FAILED, 
            PaymentStatus::REFUNDED
        ]);
    }
}
```

### Step 4: Create Payment Controller

Now, let's create our payment controller:

```bash
php artisan make:controller Api/PaymentController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    /**
     * Initialize Stripe with our secret key
     */
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for an order
     * 
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request, Order $order)
    {
        // Validate request
        $request->validate([
            'provider' => 'required|string|in:' . implode(',', PaymentProvider::values()),
        ]);

        // Check order ownership
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized. This order does not belong to you.'
            ], 403);
        }

        // Check if order can be paid
        if (!$order->canAcceptPayment()) {
            return response()->json([
                'message' => 'This order cannot be paid.',
                'status' => $order->payment_status->value
            ], 400);
        }

        // Route to correct payment provider handler
        $provider = PaymentProvider::from($request->provider);
        
        if ($provider === PaymentProvider::STRIPE) {
            return $this->createStripePayment($order);
        } else {
            // For now, we'll only implement Stripe
            return response()->json([
                'message' => 'Payment provider not implemented yet.'
            ], 501);
        }
    }

    /**
     * Create a Stripe payment intent for this order
     * 
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createStripePayment(Order $order)
    {
        try {
            // Create a payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider' => PaymentProvider::STRIPE,
                'amount' => $order->total,
                'currency' => 'usd', // Hardcoded for this example
                'status' => PaymentStatus::PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'created_at' => now()->toIso8601String(),
                ]
            ]);

            // Create a payment intent using Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($order->total * 100), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id
                ],
                'description' => "Payment for Order #{$order->order_number}",
            ]);

            // Update payment record with payment intent ID
            $payment->update([
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'client_secret' => $paymentIntent->client_secret
                ]),
            ]);

            // Return payment details to frontend
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'publishable_key' => config('services.stripe.key'),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm a payment status (used by frontend after payment)
     * 
     * @param Request $request
     * @param int $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmPayment(Request $request, $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        // Check payment ownership
        if ($payment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized. This payment does not belong to you.'
            ], 403);
        }

        return response()->json([
            'payment' => $payment,
            'order' => $payment->order,
        ]);
    }

    /**
     * Process Stripe webhooks
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook.secret');

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            
            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($event->data->object);
                
                case 'payment_intent.payment_failed':
                    return $this->handleFailedPayment($event->data->object);
                
                default:
                    Log::info('Unhandled Stripe webhook: ' . $event->type);
                    return response()->json(['status' => 'ignored']);
            }
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Invalid webhook payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Invalid webhook signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    /**
     * Handle successful payment webhook
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSuccessfulPayment($paymentIntent)
    {
        // Find payment by payment intent ID
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        // If no payment found, try to find it in metadata
        if (!$payment && isset($paymentIntent->metadata->payment_id)) {
            $payment = Payment::find($paymentIntent->metadata->payment_id);
        }
        
        if (!$payment) {
            Log::error("Payment not found for intent: " . $paymentIntent->id);
            return response()->json(['status' => 'payment-not-found']);
        }
        
        // Only process if payment is not already completed
        if ($payment->status !== PaymentStatus::COMPLETED) {
            // Mark payment as completed
            $payment->markAsCompleted($paymentIntent->id, [
                'stripe_data' => [
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'payment_method' => $paymentIntent->payment_method,
                    'status' => $paymentIntent->status,
                    'completed_at' => now()->toIso8601String(),
                ]
            ]);
            
            Log::info("Payment {$payment->id} marked as completed via webhook");
        }
        
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle failed payment webhook
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleFailedPayment($paymentIntent)
    {
        // Find payment by payment intent ID
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        // If no payment found, try to find it in metadata
        if (!$payment && isset($paymentIntent->metadata->payment_id)) {
            $payment = Payment::find($paymentIntent->metadata->payment_id);
        }
        
        if (!$payment) {
            Log::error("Payment not found for failed intent: " . $paymentIntent->id);
            return response()->json(['status' => 'payment-not-found']);
        }
        
        // Only mark as failed if not already in a final state
        if (!$payment->isFinal()) {
            // Mark payment as failed
            $payment->markAsFailed([
                'stripe_data' => [
                    'error' => $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : 'Unknown error',
                    'status' => $paymentIntent->status,
                    'failed_at' => now()->toIso8601String(),
                ]
            ]);
            
            Log::info("Payment {$payment->id} marked as failed via webhook");
        }
        
        return response()->json(['status' => 'success']);
    }
}
```

### Step 5: Configure Environment Variables

Add these settings to your `.env` file:

```
STRIPE_KEY=your_publishable_key
STRIPE_SECRET=your_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

And update your `config/services.php` file:

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
],
```

### Step 6: Set Up API Routes

Add these routes to your `routes/api.php` file:

```php
<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    // Create payment (Stripe or other providers in the future)
    Route::post('/orders/{order}/payments', [PaymentController::class, 'createPayment']);
    
    // Confirm payment status
    Route::get('/payments/{paymentId}/confirm', [PaymentController::class, 'confirmPayment']);
});

// Webhook endpoints (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])
    ->name('webhook.stripe')
    ->withoutMiddleware(['auth:sanctum', 'throttle']);
```
