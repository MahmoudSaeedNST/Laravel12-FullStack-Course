# Module 4: Payment Integration - Part 1: Refining Orders

## Introduction
Before integrating payment gateways, we need to prepare our Order model by implementing PHP Enums for status tracking. Enums provide type safety and prevent invalid values, making our payment system more robust. In this part, we'll refine our order structure to track payment states effectively.

## Let's Build: Payment-Ready Order Structure

### Step 1: Create Order Status Enum

```bash
php artisan make:enum OrderStatus
```

```php
<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';    // Initial state when order is created
    case PAID = 'paid';          // Payment received
    case PROCESSING = 'processing'; // Preparing the order
    case SHIPPED = 'shipped';    // Order sent to delivery
    case DELIVERED = 'delivered'; // Order received by customer
    case CANCELLED = 'cancelled'; // Order cancelled
    
    // Helper method to get all statuses as array
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Step 2: Create Payment Status Enum

```bash
php artisan make:enum PaymentStatus
```

```php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';     // Awaiting payment
    case COMPLETED = 'completed'; // Payment successful
    case FAILED = 'failed';       // Payment failed
    case REFUNDED = 'refunded';   // Payment refunded
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Step 3: Update Orders Migration

```bash
php artisan make:migration add_payment_fields_to_orders_table --table=orders
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
        Schema::table('orders', function (Blueprint $table) {
            // Add payment tracking fields
            $table->string('transaction_id')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'paid_at']);
        });
    }
};
```

### Step 4: Update Order Model

```php
<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'shipping_name', 'shipping_address',
        'shipping_city', 'shipping_state', 'shipping_zipcode',
        'shipping_country', 'shipping_phone', 'subtotal', 'tax',
        'shipping_cost', 'total', 'payment_method', 'payment_status',
        'transaction_id', 'paid_at', 'order_number', 'notes',
    ];
    
    // Use enum casting for statuses
    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    // Relations remain the same
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Payment specific methods
    
    /**
     * Mark an order as paid with transaction details
     * 
     * @param string $transactionId The payment gateway transaction ID
     * @return void
     */
    public function markAsPaid(string $transactionId): void
    {
        $this->update([
            'status' => OrderStatus::PAID,
            'payment_status' => PaymentStatus::COMPLETED,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }
    
    /**
     * Mark payment as failed
     * 
     * @return void
     */
    public function markPaymentFailed(): void
    {
        $this->update([
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }
    
    /**
     * Generate a unique order number
     * 
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
```

### Step 5: Update CheckoutController

We'll update the checkout process to use our new enum types:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // Validation remains the same
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_zipcode' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:stripe,paypal', // Simplified for now
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $cartItems = $user->cartItems()->with('product')->get();

        // Check if cart is empty
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        // Calculate totals
        $subtotal = 0;
        $items = [];

        // Process each cart item
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            
            // Check product availability and stock
            if (!$product->is_active) {
                return response()->json([
                    'message' => "Product '{$product->name}' is no longer available"
                ], 400);
            }

            if ($product->stock < $cartItem->quantity) {
                return response()->json([
                    'message' => "Not enough stock for '{$product->name}'"
                ], 400);
            }

            // Calculate item subtotal
            $itemSubtotal = $product->price * $cartItem->quantity;
            $subtotal += $itemSubtotal;

            // Prepare order item data
            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $cartItem->quantity,
                'price' => $product->price,
                'subtotal' => $itemSubtotal,
            ];
        }

        // Calculate tax and total
        $tax = round($subtotal * 0.08, 2);
        $shippingCost = 5.00;
        $total = $subtotal + $tax + $shippingCost;

        // Use transactions for data integrity
        DB::beginTransaction();
        try {
            // Create new order with enum values
            $order = new Order([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING, // Using enum
                'shipping_name' => $request->shipping_name,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zipcode' => $request->shipping_zipcode,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => PaymentStatus::PENDING, // Using enum
                'order_number' => Order::generateOrderNumber(),
                'notes' => $request->notes,
            ]);

            $user->orders()->save($order);

            // Create order items and update stock
            foreach ($items as $item) {
                $order->items()->create($item);
                
                // Decrease product stock
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }

            // Clear the cart
            $user->cartItems()->delete();
            
            DB::commit();

            // Return success with payment next step
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items'),
                'payment_next_step' => [
                    'type' => 'redirect',
                    'url' => route('payment.process', ['order' => $order->id, 'provider' => $request->payment_method])
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Other methods remain the same
}
```

### Step 6: Add Routes Placeholder for Payment Processing

```php
// In routes/api.php
// payment processing
    Route::get('payment/process/{order}/{provider}', [PaymentController::class, 'processPayment'])
        ->name('payment.process');
```

## What We've Learned

In this part, we've:

1. **Enhanced our order structure** with PHP Enums for better type safety
2. **Added payment tracking fields** to record transaction IDs and timestamps
3. **Created helper methods** in the Order model to manage payment state transitions
4. **Updated the checkout process** to provide payment next steps to the client

Enums provide several advantages over simple strings:
- They restrict values to a predefined set, preventing invalid statuses
- IDEs can provide autocompletion and validation
- You can attach methods to them for behavior specific to each status

## Practice Challenge

**Task:** Create a simple `PaymentProviderEnum` and an `OrderController` method that allows admins to manually mark an order as paid (useful for bank transfers or cash on delivery).

1. Create an enum for payment providers
2. Create an admin-only endpoint to mark orders as paid
3. Implement validation to ensure only pending orders can be marked as paid
4. Return appropriate error messages for invalid state transitions

This exercise will help solidify your understanding of Enums and order state management before we dive into actual payment gateways.