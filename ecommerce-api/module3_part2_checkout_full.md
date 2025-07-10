
# Module 3 - Part 2: Checkout and Order Creation

## Introduction

The checkout process converts a user's shopping cart into a permanent order. It stores essential data such as shipping info, totals, items, and payment details. We'll walk through building:

- Orders and OrderItems migrations
- Full models and relationships
- CheckoutController logic
- Authenticated API routes

---

## Step 1: Migrations

### `create_orders_table.php`

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('status')->default('pending');
    $table->string('shipping_name');
    $table->string('shipping_address');
    $table->string('shipping_city');
    $table->string('shipping_state')->nullable();
    $table->string('shipping_zipcode');
    $table->string('shipping_country');
    $table->string('shipping_phone');
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('shipping_cost', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->string('payment_method')->nullable();
    $table->string('payment_status')->default('pending');
    $table->string('order_number')->unique();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### `create_order_items_table.php`

```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained();
    $table->string('product_name');
    $table->string('product_sku');
    $table->integer('quantity');
    $table->decimal('price', 10, 2);
    $table->decimal('subtotal', 10, 2);
    $table->timestamps();
});
```

---

## Step 2: Models

### `Order.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'shipping_name', 'shipping_address',
        'shipping_city', 'shipping_state', 'shipping_zipcode',
        'shipping_country', 'shipping_phone', 'subtotal', 'tax',
        'shipping_cost', 'total', 'payment_method', 'payment_status',
        'order_number', 'notes',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber() {
        $year = date('Y');
        $random = strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$random}";
    }

    public function canBeCancelled() {
        return in_array($this->status, ['pending', 'paid']);
    }
}
```

### `OrderItem.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_sku',
        'quantity', 'price', 'subtotal'
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
```

### Add to `User.php`

```php
public function orders() {
    return $this->hasMany(Order::class);
}
```

---

## Step 3: CheckoutController

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_zipcode' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:credit_card,paypal',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        $subtotal = 0;
        $items = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            if (!$product->is_active) {
                return response()->json(['message' => "Product '{$product->name}' is no longer available"], 400);
            }

            if ($product->stock < $cartItem->quantity) {
                return response()->json(['message' => "Not enough stock for '{$product->name}'"], 400);
            }

            $itemSubtotal = $product->price * $cartItem->quantity;
            $subtotal += $itemSubtotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $cartItem->quantity,
                'price' => $product->price,
                'subtotal' => $itemSubtotal,
            ];
        }

        $tax = round($subtotal * 0.08, 2);
        $shippingCost = 5.00;
        $total = $subtotal + $tax + $shippingCost;

        DB::beginTransaction();
        try {
            $order = new Order([
                'user_id' => $user->id,
                'status' => 'pending',
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
                'payment_status' => 'pending',
                'order_number' => Order::generateOrderNumber(),
                'notes' => $request->notes,
            ]);

            $user->orders()->save($order);

            foreach ($items as $item) {
                $order->items()->create($item);
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }

            $user->cartItems()->delete();
            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function orderHistory(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->orderBy('created_at', 'desc')->get();
        return response()->json(['orders' => $orders]);
    }

    public function orderDetails(Request $request, $orderId)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($orderId);
        return response()->json(['order' => $order]);
    }
}
```

---

## Step 4: Routes

```php
use App\Http\Controllers\Api\CheckoutController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::get('/orders', [CheckoutController::class, 'orderHistory']);
    Route::get('/orders/{orderId}', [CheckoutController::class, 'orderDetails']);
});
```

---

## Summary

- We created `orders` and `order_items` tables
- Stored customer shipping info and financial breakdown
- Built a reliable checkout process using validation and DB transactions
- Setup full models and routes for order creation and retrieval

