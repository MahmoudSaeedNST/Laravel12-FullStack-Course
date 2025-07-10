# Module 4: Payment Integration - Part 3: PayPal API Integration

## Introduction
PayPal remains one of the most popular payment methods worldwide. In this part, we'll integrate PayPal's REST API directly. PayPal offers a secure checkout experience where customers can pay with their PayPal account or credit card without leaving your application. We'll implement PayPal Orders API v2 which is the current standard for e-commerce integrations.
## Let's Build: PayPal Orders API Integration


### Step 1: Create PayPal Service Class

Let's create a dedicated service class to handle PayPal API calls:

```bash
php artisan make:class Services/PayPalService
```

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PayPalService
{
    /**
     * PayPal API base URL (sandbox or live)
     */
    private string $baseUrl;
    
    /**
     * PayPal client ID
     */
    private string $clientId;
    
    /**
     * PayPal client secret
     */
    private string $clientSecret;
    
    /**
     * Access token for API calls
     */
    private ?string $accessToken = null;

    public function __construct()
    {
        // Initialize PayPal configuration from environment
        $this->baseUrl = config('services.paypal.mode') === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
            
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
    }

    /**
     * Get access token from PayPal OAuth API
     * 
     * @return string
     * @throws Exception
     */
    private function getAccessToken(): string
    {
        // Return cached token if available
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            // Make OAuth request to PayPal
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                throw new Exception('Failed to get PayPal access token: ' . $response->body());
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'];
            
            return $this->accessToken;
            
        } catch (Exception $e) {
            Log::error('PayPal OAuth error: ' . $e->getMessage());
            throw new Exception('PayPal authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a PayPal order
     * 
     * @param float $amount Order total amount
     * @param string $currency Currency code (default: USD)
     * @param array $metadata Additional order metadata
     * @return array PayPal order response
     * @throws Exception
     */
    public function createOrder(float $amount, string $currency = 'USD', array $metadata = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Prepare order data for PayPal API
            $orderData = [
                'intent' => 'CAPTURE',                    // Capture payment immediately
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),  // Format to 2 decimal places
                        ],
                        'description' => $metadata['description'] ?? 'Order Payment',
                        'reference_id' => $metadata['order_number'] ?? uniqid('order_'),
                    ]
                ],
                'application_context' => [
                    'return_url' => $metadata['return_url'] ?? config('app.url') . '/payment/success',
                    'cancel_url' => $metadata['cancel_url'] ?? config('app.url') . '/payment/cancel',
                    'brand_name' => config('app.name'),
                    'landing_page' => 'NO_PREFERENCE',     // Let PayPal decide the best landing page
                    'user_action' => 'PAY_NOW',            // Show "Pay Now" instead of "Continue"
                ]
            ];

            // Make API call to create PayPal order
            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post($this->baseUrl . '/v2/checkout/orders', $orderData);

            if (!$response->successful()) {
                Log::error('PayPal create order error: ' . $response->body());
                throw new Exception('Failed to create PayPal order: ' . $response->body());
            }

            return $response->json();
            
        } catch (Exception $e) {
            Log::error('PayPal create order exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Capture a PayPal order (complete the payment)
     * 
     * @param string $orderId PayPal order ID
     * @return array PayPal capture response
     * @throws Exception
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Make API call to capture the PayPal order
            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture");

            if (!$response->successful()) {
                Log::error('PayPal capture order error: ' . $response->body());
                throw new Exception('Failed to capture PayPal order: ' . $response->body());
            }

            return $response->json();
            
        } catch (Exception $e) {
            Log::error('PayPal capture order exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get details of a PayPal order
     * 
     * @param string $orderId PayPal order ID
     * @return array PayPal order details
     * @throws Exception
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Make API call to get PayPal order details
            $response = Http::withToken($accessToken)
                ->get($this->baseUrl . "/v2/checkout/orders/{$orderId}");

            if (!$response->successful()) {
                Log::error('PayPal get order error: ' . $response->body());
                throw new Exception('Failed to get PayPal order details: ' . $response->body());
            }

            return $response->json();
            
        } catch (Exception $e) {
            Log::error('PayPal get order exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
```

### Step 2: Update Payment Model

Add PayPal-specific fields to track PayPal order IDs:

```bash
php artisan make:migration add_paypal_fields_to_payments_table --table=payments
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add PayPal-specific tracking fields
            $table->string('paypal_order_id')->nullable()->after('payment_intent_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['paypal_order_id', 'paypal_capture_id']);
        });
    }
};
```

Update the Payment model:

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
        'paypal_order_id',          // New PayPal field
        'paypal_capture_id',        // New PayPal field
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at'
    ];

    // ... existing casts and relationships remain the same

    /**
     * Mark PayPal payment as completed
     * 
     * @param string $paypalCaptureId The capture ID from PayPal
     * @param array $metadata Additional metadata to store
     * @return void
     */
    public function markAsCompletedPayPal(string $paypalCaptureId, array $metadata = []): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'paypal_capture_id' => $paypalCaptureId,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
        ]);

        // Also update the associated order
        $this->order->markAsPaid($paypalCaptureId);
    }
}
```

### Step 3: Update PaymentController

Add PayPal payment methods to our existing PaymentController:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ... existing Stripe methods remain the same

    /**
     * Create a PayPal payment for an order
     * 
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createPayPalPayment(Order $order)
    {
        try {
            $paypalService = new PayPalService();
            
            // Create a payment record first
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider' => PaymentProvider::PAYPAL,
                'amount' => $order->total,
                'currency' => 'USD',                    // Hardcoded for this example
                'status' => PaymentStatus::PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'created_at' => now()->toIso8601String(),
                ]
            ]);

            // Prepare metadata for PayPal order creation
            $metadata = [
                'description' => "Payment for Order #{$order->order_number}",
                'order_number' => $order->order_number,
                'return_url' => config('app.url') . "/api/payments/paypal/success?payment_id={$payment->id}",
                'cancel_url' => config('app.url') . "/api/payments/paypal/cancel?payment_id={$payment->id}",
            ];

            // Create PayPal order using our service
            $paypalOrder = $paypalService->createOrder($order->total, 'USD', $metadata);

            // Update payment record with PayPal order ID
            $payment->update([
                'paypal_order_id' => $paypalOrder['id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'paypal_order' => $paypalOrder
                ]),
            ]);

            // Extract approval URL from PayPal response
            $approvalUrl = null;
            foreach ($paypalOrder['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            // Return payment details to frontend
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'paypal_order_id' => $paypalOrder['id'],
                'approval_url' => $approvalUrl,
                'status' => $paypalOrder['status'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('PayPal payment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'PayPal payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the main createPayment method to handle PayPal
     */
    public function createPayment(Request $request, Order $order)
    {
        // ... existing validation code

        // Route to correct payment provider handler
        $provider = PaymentProvider::from($request->provider);
        
        if ($provider === PaymentProvider::STRIPE) {
            return $this->createStripePayment($order);
        } elseif ($provider === PaymentProvider::PAYPAL) {
            return $this->createPayPalPayment($order);
        } else {
            return response()->json([
                'message' => 'Payment provider not supported.'
            ], 501);
        }
    }

    /**
     * Handle PayPal payment success callback
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypalSuccess(Request $request)
    {
        try {
            $paymentId = $request->query('payment_id');
            $paypalOrderId = $request->query('token');       // PayPal returns order ID as 'token'
            
            if (!$paymentId || !$paypalOrderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }

            // Find our payment record
            $payment = Payment::findOrFail($paymentId);
            
            if ($payment->status === PaymentStatus::COMPLETED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'payment' => $payment
                ]);
            }

            // Capture the PayPal payment
            $paypalService = new PayPalService();
            $captureResult = $paypalService->captureOrder($paypalOrderId);

            // Check if capture was successful
            if ($captureResult['status'] === 'COMPLETED') {
                // Extract capture ID from PayPal response
                $captureId = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'];
                
                // Mark payment as completed
                $payment->markAsCompletedPayPal($captureId, [
                    'paypal_capture_result' => $captureResult,
                    'completed_at' => now()->toIso8601String(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'payment' => $payment->fresh(),
                    'order' => $payment->order
                ]);
            } else {
                $payment->markAsFailed([
                    'paypal_error' => 'Capture failed',
                    'paypal_response' => $captureResult
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('PayPal success callback error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle PayPal payment cancellation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypalCancel(Request $request)
    {
        try {
            $paymentId = $request->query('payment_id');
            
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing payment ID'
                ], 400);
            }

            // Find our payment record and mark as failed
            $payment = Payment::findOrFail($paymentId);
            $payment->markAsFailed([
                'reason' => 'User cancelled payment',
                'cancelled_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment was cancelled by user',
                'payment' => $payment
            ]);
            
        } catch (\Exception $e) {
            Log::error('PayPal cancel callback error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing cancellation'
            ], 500);
        }
    }
}
```

### Step 5: Configure Environment Variables

Add PayPal configuration to your `.env` file:

```env
# PayPal Configuration
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_paypal_sandbox_client_id
PAYPAL_CLIENT_SECRET=your_paypal_sandbox_client_secret
```

Update your `config/services.php`:

```php
'paypal' => [
    'mode' => env('PAYPAL_MODE', 'sandbox'),  // 'sandbox' or 'live'
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
],
```

### Step 6: Update API Routes

Add PayPal-specific routes to `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    // Create payment (Stripe, PayPal, or other providers)
    Route::post('/orders/{order}/payments', [PaymentController::class, 'createPayment']);
    
    // Confirm payment status
    Route::get('/payments/{paymentId}/confirm', [PaymentController::class, 'confirmPayment']);
});

// PayPal callback routes (no authentication required)
Route::get('/payments/paypal/success', [PaymentController::class, 'paypalSuccess'])
    ->name('paypal.success');
Route::get('/payments/paypal/cancel', [PaymentController::class, 'paypalCancel'])
    ->name('paypal.cancel');

// Webhook endpoints (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])
    ->name('webhook.stripe')
    ->withoutMiddleware(['auth:sanctum', 'throttle']);
```

## What We've Learned

In this part, we've:

1. **Implemented PayPal Orders API v2** using Laravel's HTTP client for modern integration
2. **Created a dedicated PayPalService** to encapsulate all PayPal API interactions
3. **Added PayPal-specific tracking fields** to our Payment model
4. **Built a complete payment flow** from creation to capture with proper error handling
5. **Used current 2025 standards** by avoiding deprecated SDK packages

The PayPal integration follows the modern Orders v2 API pattern recommended by PayPal, providing a secure and scalable payment solution.

## Real-World Challenge

**Practice Exercise**: Implement a PayPal refund system! Create a new method in `PayPalService` called `refundCapture()` that can refund a completed PayPal payment. Add a new API endpoint `/payments/{paymentId}/refund` that allows admins to issue refunds and updates the payment status to `REFUNDED`.

**Requirements:**
1. Use PayPal's Captures API to process refunds
2. Add proper validation to ensure only completed payments can be refunded  
3. Update the Payment model with refund tracking fields
4. Implement partial refund capability (refund amount less than original payment)

