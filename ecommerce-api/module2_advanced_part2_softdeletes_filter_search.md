
# Module 2 - Advanced Part: Soft Deletes + Filtering + Search Improvements

## Introduction

In this lesson, we’ll upgrade our Products API with powerful data management tools:

- Enable safe deletion with **Soft Deletes**
- Add advanced filtering for price, stock, and status
- Implement a simple search across name and description

## Part 1: Enable Soft Deletes

**Goal:** Keep deleted products in the database for recovery or audit.

### Step 1: Update Product Migration

Add `softDeletes()` to your migration:

```php
$table->softDeletes();
```

Then run:

```bash
php artisan migrate
```

### Step 2: Enable in the Model

In `Product.php`:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
}
```

### Step 3: Restore / Force Delete (Optional for Admin)

- `Product::onlyTrashed()` → get deleted only  
- `Product::withTrashed()` → get all  
- `Product::restore()` → undo delete  
- `Product::forceDelete()` → permanent delete

## Part 2: Advanced Filtering in Controller

Enhance the `filteredProducts()` method in `ProductController.php` to allow dynamic filtering.

```php
public function filteredProducts(Request $request)
{
    $products = Product::query()
        ->when($request->has('price_min'), fn($q) => $q->where('price', '>=', $request->price_min))
        ->when($request->has('price_max'), fn($q) => $q->where('price', '<=', $request->price_max))
        ->when($request->has('stock_min'), fn($q) => $q->where('stock', '>=', $request->stock_min))
        ->when($request->has('q'), function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('description', 'like', '%' . $request->q . '%');
            });
        })
        ->get();

    return response()->json([
        'products' => $products
    ]);
}
```

**Highlight:** Use `when()` for clean, optional query filters.  
**Supports:** Search by name or description.

## Optional: Include Trashed Products for Admin

```php
if ($request->user()?->hasRole('admin') && $request->include_trashed) {
    $products = $products->withTrashed();
}
```

## Recap

- Added Soft Deletes with Laravel’s built-in feature.
- Created dynamic filters using `when()`.
- Implemented flexible search logic.
- Added option to include trashed products.
