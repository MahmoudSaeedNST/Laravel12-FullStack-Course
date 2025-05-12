
# Module 2 - Part 3: Product-Category Relationships

## Introduction

In e-commerce systems, products typically belong to one or more categories.  
This is a classic many-to-many relationship — a product can be in multiple categories (like "Electronics" and "Computers"), and a category can contain many products.  
In Laravel, we implement this using a pivot table that connects products and categories.

---

## Let's Build: Product-Category Relationships

### Step 1: Create Migration for the Pivot Table

```bash
php artisan make:migration create_category_product_table
```

Migration file structure:

```php
Schema::create('category_product', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('category_id')->constrained()->onDelete('cascade');
});
```

Run the migration:

```bash
php artisan migrate
```

---

### Step 2: Update Models

**In `Product.php`:**

```php
public function categories()
{
    return $this->belongsToMany(Category::class);
}
```

**In `Category.php`:**

```php
public function products()
{
    return $this->belongsToMany(Product::class);
}

public function activeProducts()
{
    return $this->products()->where('is_active', true);
}
```

---

### Step 3: Update ProductController

Attach categories when creating:

```php
// in validation 
            'categories' => 'sometimes|array', // Optional array of category IDs
            'categories.*' => 'exists:categories,id', // Each ID must exist in categories table

 // Attach categories if provided
        if ($request->has('categories')) {
            $product->categories()->attach($request->categories);
        }
        
        // Load the categories relation for the response
        $product->load('categories');
}
```

Sync categories when updating:

```php
// in validation 
            'categories' => 'sometimes|array', // Optional array of category IDs
            'categories.*' => 'exists:categories,id', // Each ID must exist in categories table
// Update categories if provided
        if ($request->has('categories')) {
            // sync() replaces all existing relationships with the new ones
            $product->categories()->sync($request->categories);
        }
        
        // Load categories for response
        $product->load('categories');
```

Filter by category in `index()`:

```php
if ($request->has('category_id')) {
    $query->whereHas('categories', function($q) use ($request) {
        $q->where('categories.id', $request->category_id);
    });
}
```

---

### Step 4: Category → Products Endpoint

In `CategoryController`:

```php
public function products(Category $category)
{
    $products = $category->products()->where('is_active', true)->get();

    return response()->json([
        'category' => $category->name,
        'products' => $products
    ]);
}
```

---

### Step 5: Update Routes

```php
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);
```

---

## What We've Learned

- Created a pivot table for many-to-many relationships.
- Defined relationships in both `Product` and `Category` models.
- Attached and synced categories with products.
- Filtered products by category in the API.
- Created a route to list products inside a category.

---

## Practice Challenge

- Add `is_featured` column to products table.
- Add scope in Product model:

```php
public function scopeFeatured($query)
{
    return $query->where('is_featured', true);
}
```

- Create an endpoint `/api/products/featured` to return active featured products.
