
# Module 3 - Part 1: Shopping Cart APIs (Add / Update / Remove)

## Goal

Build a database-backed cart system that allows authenticated users to:

- Add a product to their cart
- Update quantity of products in their cart
- Remove products from the cart
- Retrieve their current cart with total price

---

## 🛠 Step 1: Create the Migration

```bash
php artisan make:migration create_carts_table
```

Edit the generated migration file:

```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->integer('quantity')->default(1);
    $table->timestamps();

    $table->unique(['user_id', 'product_id']);
});
```

Run the migration:

```bash
php artisan migrate
```

---

## 📦 Step 2: Create the Cart Model

```bash
php artisan make:model Cart
```

Inside `app/Models/Cart.php`:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

## 🧠 Step 3: Create CartController

```bash
php artisan make:controller Api/CartController
```

Inside `app/Http/Controllers/Api/CartController.php`:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cartItems = Cart::with('product')
                        ->where('user_id', $user->id)
                        ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'success' => true,
            'cart' => $cartItems,
            'total' => $total
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();

        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $data['product_id'])
                        ->first();

        if ($cartItem) {
            $cartItem->quantity += $data['quantity'];
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_item' => $cartItem
        ], 201);
    }

    public function update(Request $request, Cart $cart)
    {
        $this->authorize('update', $cart);

        $data = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart->quantity = $data['quantity'];
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'cart_item' => $cart
        ]);
    }

    public function destroy(Request $request, Cart $cart)
    {
        $this->authorize('delete', $cart);

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed'
        ]);
    }
}
```

---

## 🔗 Step 4: Add Routes

In `routes/api.php`:

```php
use App\Http\Controllers\Api\CartController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
});
```

---

## ✅ Summary

- Cart is stored in the database using `user_id`
- Each product can only appear once per user (enforced by unique key)
- Quantity can be updated or removed
- Full CRUD API with security via Sanctum middleware

---

## 🚀 Next

Next part: **Checkout API – Create Orders**  
We’ll take the cart contents and create order records with proper validations and totals.
