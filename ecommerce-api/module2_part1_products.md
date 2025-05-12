
# Module 2 - Part 1: Products Management

## Introduction

Products are the heart of any e-commerce system.  
A well-structured Product entity needs to store:
- Basic info (name, slug)
- Price
- Stock quantity
- SKU
- Status (active/inactive)

In this lesson, we’ll build:
- Full CRUD for products
- API-ready resources
- Secure validation

**Key Concept:**  
Products must be cleanly structured to be extendable later with categories, images, attributes, discounts, etc.

## Step 1: Create Products Migration

Command:
```bash
php artisan make:migration create_products_table
```

Migration structure:
```php
$table->id();
$table->string('name');
$table->string('slug')->unique();
$table->text('description')->nullable();
$table->decimal('price', 10, 2);
$table->integer('stock')->default(0);
$table->string('sku')->unique();
$table->boolean('is_active')->default(true);
$table->timestamps();
```

Run migration:
```bash
php artisan migrate
```

**Highlight:** Always set unique constraints on fields like `slug` and `sku`.

## Step 2: Create Product Model

Command:
```bash
php artisan make:model Product
```

Inside `Product.php`:
```php
protected $fillable = [
    'name', 'slug', 'description', 'price', 'stock', 'sku', 'is_active'
];

public function inStock()
{
    return $this->stock > 0;
}
```

**Highlight:** Use `fillable` to protect your model from mass assignment vulnerabilities.

## Step 3: Create Product Controller

Command:
```bash
php artisan make:controller Api/ProductController --api
```

Methods:
- `index` → List products
- `store` → Create new product
- `show` → View one product
- `update` → Update product
- `destroy` → Delete product

Example `store()` method:
```php
$request->validate([
    'name' => 'required|string|max:255',
    'price' => 'required|numeric|min:0',
    'stock' => 'required|integer|min:0',
    'sku' => 'required|string|unique:products',
]);

$product = Product::create([
    'name' => $request->name,
    'slug' => Str::slug($request->name),
    'description' => $request->description,
    'price' => $request->price,
    'stock' => $request->stock,
    'sku' => $request->sku,
    'is_active' => true
]);
```

**Highlight:** Always auto-generate `slug` from `name` for clean URLs.

## Step 4: Set Up API Routes

Inside `routes/api.php`:
```php
Route::apiResource('products', ProductController::class)->only([
    'index', 'show'
]);

Route::middleware(['auth:sanctum', 'permission:create products'])->group(function () {
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
});
```

**Highlight:** Public can view products, but only Admins can create, update, or delete.

## Recap

We have now:
- Created database structure for products
- Built the Eloquent model
- Made full CRUD API
- Protected routes and validated inputs


## Practice Exercise: 

### Add a product search feature: 

**Task:** 
Create a new method in your ProductController called search that accepts a GET parameter q and returns products that match the search term in either name or description. Make sure to only search among active products.


---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---
---

## Practice Challenge

**Task:**  
Create a custom scope method in the Product model:

```php
public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

**Bonus:**  
Use it inside your `index()` method:

```php
$products = Product::active()->get();
```

## Time Management Estimate

| Section | Estimated Time |
|:---|:---|
| Introduction | 1 min |
| Step 1: Migration | 2 min |
| Step 2: Model | 2 min |
| Step 3: Controller | 3-4 min |
| Step 4: Routes | 2 min |
| Recap + Challenge | 1-2 min |

**Total:** ~10-12 minutes
