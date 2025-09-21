# Module 5: Order Management and Realtime Updates - Part 1: Order Status Management & Workflow

## Introduction

Order status management is the backbone of any e-commerce system. Think of it like a package journey - from "pending" when first created, to "paid" after payment, then "processing" when being prepared, "shipped" when sent out, and finally "delivered" when received. Each status change needs validation rules (you can't ship an unpaid order) and should trigger actions like sending notifications. We'll build a robust system that tracks these transitions and ensures business rules are followed.

## Let's Build: Order Status Transition System

We're going to enhance our existing Order model with intelligent status transitions. Instead of allowing any status change, we'll create a system that validates transitions (like ensuring an order is paid before it can be shipped), tracks status history for auditing, and provides admin-friendly endpoints to manage orders. This approach keeps business logic centralized and prevents invalid state changes that could break our e-commerce workflow.

### Step 1: Enhance OrderStatus Enum with Transition Logic

Let's update our existing `OrderStatus` enum to include transition rules:

```php
<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';        // Order created, awaiting payment
    case PAID = 'paid';             // Payment received successfully  
    case PROCESSING = 'processing';  // Order being prepared for shipment
    case SHIPPED = 'shipped';       // Order sent to customer
    case DELIVERED = 'delivered';   // Order received by customer
    case CANCELLED = 'cancelled';   // Order cancelled (only from pending/paid)

    /**
     * Get all allowed transitions FROM this status TO other statuses
     * This defines our business rules for state changes
     * 
     * @return array Array of OrderStatus enums that this status can transition to
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            // Pending orders can be paid or cancelled
            self::PENDING => [self::PAID, self::CANCELLED],
            
            // Paid orders can move to processing or be cancelled (refund scenario)
            self::PAID => [self::PROCESSING, self::CANCELLED],
            
            // Processing orders can be shipped or cancelled (inventory issues)
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            
            // Shipped orders can only be delivered (final happy path)
            self::SHIPPED => [self::DELIVERED],
            
            // Delivered and cancelled are final states - no transitions allowed
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if this status can transition to the target status
     * 
     * @param OrderStatus $targetStatus The status we want to change to
     * @return bool True if transition is allowed, false otherwise
     */
    public function canTransitionTo(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAllowedTransitions());
    }

    /**
     * Get human-readable label for this status
     * 
     * @return string Friendly display name for the status
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending Payment',
            self::PAID => 'Payment Confirmed',
            self::PROCESSING => 'Being Prepared',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get CSS class for status display (useful for frontend styling)
     * 
     * @return string CSS class name
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::PENDING => 'status-warning',
            self::PAID => 'status-info', 
            self::PROCESSING => 'status-primary',
            self::SHIPPED => 'status-success',
            self::DELIVERED => 'status-success',
            self::CANCELLED => 'status-danger',
        };
    }

    /**
     * Helper method to get all statuses as array for validation rules
     * 
     * @return array All possible status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### for Example:
When you call:
```php
$currentStatus->canTransitionTo($targetStatus);
```
PHP will internally do:
```php
in_array($targetStatus, $this->getAllowedTransitions());
```
So getAllowedTransitions() needs to return something like:

```php
[
    OrderStatus::PAID,
    OrderStatus::CANCELLED
]
```
if $this ($currentStatus) is OrderStatus::PENDING.
```php
$currentStatus = OrderStatus::PENDING;

if ($currentStatus->canTransitionTo(OrderStatus::PAID)) {
    echo "Yes, you can change from pending to paid.";
} else {
    echo "No, that transition is not allowed.";
}
```
If $currentStatus is PENDING,
getAllowedTransitions() returns [PAID, CANCELLED], so canTransitionTo(PAID) will be true.

If $currentStatus is DELIVERED,
getAllowedTransitions() returns [], so canTransitionTo(SHIPPED) will be false.

### Step 2: Create Order History Tracking

We need to track when status changes happen and who made them. Let's create a migration and model for order history:

```bash
php artisan make:model OrderStatusHistory -m
```

```php
<?php
// Migration file: xxxx_xx_xx_create_order_status_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Which order
            $table->string('from_status')->nullable(); // Previous status (null for first status)
            $table->string('to_status');               // New status
            $table->foreignId('changed_by')->nullable()->constrained('users'); // Who made the change
            $table->text('notes')->nullable();         // Optional reason/comment
            $table->timestamps();                      // When the change happened
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
```

```php
<?php
// Model: app/Models/OrderStatusHistory.php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'from_status', 
        'to_status',
        'changed_by',
        'notes'
    ];

    /**
     * Cast status fields to OrderStatus enums for type safety
     */
    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
    ];

    /**
     * Get the order this history entry belongs to
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this status change
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

### Step 3: Update Order Model with Transition Methods

Now let's enhance our Order model with safe transition methods:

```php
<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{

    /**
     * Get the status history for this order (newest first)
     */
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /**
     * Safely transition order status with validation and history tracking
     * 
     * @param OrderStatus $newStatus The status we want to change to
     * @param User|null $changedBy Who is making the change (defaults to current user)
     * @param string|null $notes Optional reason for the change
     * @return bool True if transition was successful, false if not allowed
     * @throws \Exception If transition is not allowed
     */
    public function transitionTo(OrderStatus $newStatus, $changedBy = null, ?string $notes = null): bool
    {
        // Don't allow transition to the same status
        if ($this->status === $newStatus) {
            return true; // Already in target status, consider it successful
        }

        // Check if this transition is allowed by our business rules
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \Exception(
                "Cannot transition order #{$this->order_number} from {$this->status->value} to {$newStatus->value}. "
            );
        }

        // Store the old status for history
        $oldStatus = $this->status;

        // Update the order status
        $this->status = $newStatus;
        $this->save();

        // Record the status change in history
        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $changedBy?->id ?? Auth::id(), // Use provided user or current logged-in user
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Get all possible next statuses for this order
     * 
     * @return array Array of OrderStatus enums this order can transition to
     */
    public function getAvailableTransitions(): array
    {
        return $this->status->getAllowedTransitions();
    }


    /**
     * Get the latest status change from history
     * 
     * @return OrderStatusHistory|null
     */
    public function getLatestStatusChange()
    {
        return $this->statusHistory()->first();
    }

}
```

### Step 4: Create Order Management Controller

Now let's create admin endpoints to manage order statuses:

```bash
php artisan make:controller Api/OrderManagementController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{
    /**
     * Get all orders with filtering options (admin only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Build query with optional filters
        $query = Order::with(['user', 'items.product']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Get orders with pagination
        $orders = $query->latest()->paginate(15);

        return response()->json([
            'orders' => $orders,
            'available_statuses' => OrderStatus::values(),
        ]);
    }

    /**
     * Get single order details with status history (admin only)
     * 
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Order $order)
    {
        // Load all related data for admin view
        $order->load([
            'user', 
            'items.product', 
            'statusHistory.changedBy'
        ]);

        return response()->json([
            'order' => $order,
            'available_transitions' => $order->getAvailableTransitions(),
        ]);
    }

    /**
     * Update order status (admin only)
     * 
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Validate the new status
        $request->validate([
            'status' => 'required|string|in:' . implode(',', OrderStatus::values()),
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Convert string to enum
            $newStatus = OrderStatus::from($request->status);
            
            // Attempt the transition
            $order->transitionTo($newStatus, Auth::user(), $request->notes);

            // Reload order with fresh data
            $order->load(['statusHistory.changedBy']);

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$newStatus->getLabel()}",
                'order' => $order,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel an order (admin only)
     * 
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // Check if order can be cancelled
            if (!$order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be cancelled in its current status.',
                ], 400);
            }

            // Cancel the order
            $order->transitionTo(OrderStatus::CANCELLED, Auth::user(), "Cancelled: " . $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Order has been cancelled',
                'order' => $order->fresh(['statusHistory.changedBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Step 5: Add Routes for Order Management

Add these routes to your `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\OrderManagementController;
use Illuminate\Support\Facades\Route;

// Admin-only order management routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Order management endpoints
    Route::get('/admin/orders', [OrderManagementController::class, 'index']);
    Route::get('/admin/orders/{order}', [OrderManagementController::class, 'show']);
    Route::patch('/admin/orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
    Route::post('/admin/orders/{order}/cancel', [OrderManagementController::class, 'cancel']);
});
```

## What We've Learned

In this part, we've built:

1. **Smart State Transitions** - Our OrderStatus enum now validates business rules
2. **History Tracking** - Every status change is recorded with who, when, and why
3. **Safe Transition Methods** - The `transitionTo()` method prevents invalid state changes
4. **Admin Management** - Complete API endpoints for managing order statuses

The system prevents impossible transitions (like shipping an unpaid order) while maintaining a clear audit trail of all changes.

## Real-World Challenge

**Practice Exercise**: Implement an **automatic order progression system**! 

Create a new Artisan command called `ProcessPendingOrders` that:

1. Finds all PAID orders that are older than 2 hours
2. Automatically transitions them to PROCESSING status  
3. Finds all PROCESSING orders older than 24 hours
4. Automatically transitions them to SHIPPED status
5. Logs each transition with "System automation" as the reason

**Bonus points**: Schedule this command to run every hour using Laravel's task scheduler.

This exercise teaches you how to build automation around your state machine - a common requirement in real e-commerce systems!

Ready for **Part 2: Real-time Updates with Laravel Reverb** when you give me the green light! 🚀