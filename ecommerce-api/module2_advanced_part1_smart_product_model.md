
# Module 2 - Advanced Part: Smart Product Model Enhancements

## Introduction

Today, we'll upgrade our Product model to make our queries smarter, cleaner, and faster.  
We'll implement 3 powerful techniques:
- Global Scope (always return active products)
- Local Scope with dynamic parameters
- Accessor to format product names automatically

## Part 1: Global Scope - Active Products Only

**Goal:** Every time we query products, we only want `is_active = true` products by default.

Inside `Product.php`:

```php
protected static function booted()
{
    static::addGlobalScope('active', function ($query) {
        $query->where('is_active', true);
    });
}
```

Usage:

```php
Product::all();
```

**Highlight:** You can bypass it when needed using:

```php
Product::query()->withoutGlobalScope('active')->get();
```

## Part 2: Local Scope - Price Range Filtering

**Goal:** Make reusable smart queries by adding dynamic filtering.

Inside `Product.php`:

```php
public function scopePriceBetween($query, $min, $max)
{
    return $query->whereBetween('price', [$min, $max]);
}
```

Usage in Controller:

```php
$products = Product::priceBetween(100, 500)->get();
```

**Highlight:** Scopes make your queries clean, readable, and reusable.

## Part 3: Accessor - Formatted Product Name

**Goal:** Format product names automatically when accessing them.

Inside `Product.php`:

```php
public function getFormattedNameAttribute()
{
    return ucfirst($this->name);
}
```

Usage:

```php
$product = Product::first();
echo $product->formatted_name;
```

**Highlight:** Accessors allow you to add virtual fields or modify output without changing the database.

## Recap

- Added Global Scope for `is_active` filtering.
- Built a Local Scope for dynamic price filtering.
- Created a Custom Accessor for a formatted product name.

## Practice Challenge

**Task:** Create another Local Scope to filter products by minimum stock:

```php
public function scopeHasStock($query, $min = 1)
{
    return $query->where('stock', '>=', $min);
}
```

Usage:

```php
Product::hasStock(10)->get();
```

