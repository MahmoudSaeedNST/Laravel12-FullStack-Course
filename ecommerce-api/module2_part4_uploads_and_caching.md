
# Module 2 - Part 4: Product Image Uploads and Simple Caching

## Introduction

In this lesson, we'll enhance our product management API by adding support for:

- Uploading a product image using Laravel's storage system
- Retrieving the full image URL using an accessor
- Implementing simple caching for product lists and individual products

These features improve user experience and boost performance for larger product catalogs.

---

## Step 1: Add Image Column to Products Table

Run this command to add a new column to store image filenames:

```bash
php artisan make:migration add_image_to_products_table --table=products
```

Inside the generated migration:

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('image')->nullable()->after('is_active');
    });
}
```

Run the migration:

```bash
php artisan migrate
```

---

## Step 2: Update Product Model

Inside `Product.php`, make sure the `image` is fillable and add an accessor:

```php
protected $fillable = [
    'name',
    'slug',
    'description',
    'price',
    'stock',
    'sku',
    'is_active',
    'image',
];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}
```

---

## Step 3: Image Upload in Store Method

In your `store()` method inside `ProductController.php`, update validation and handle the image:

```php
$request->validate([
    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
]);

$imagePath = null;
if ($request->hasFile('image')) {
    $imagePath = $request->file('image')->store('products', 'public');
}

$data['image'] = $imagePath;
$product = Product::create($data);
```


---

## Step 4: Image Update in Update Method

Inside the `update()` method:

```php
if ($request->hasFile('image')) {
    if ($product->image) {
        Storage::disk('public')->delete($product->image);
    }
    $product->image = $request->file('image')->store('products', 'public');
}
```

---

## Step 5: Image Delete in Destroy Method

When deleting a product:

```php
if ($product->image) {
    Storage::disk('public')->delete($product->image);
}

$product->delete();
```

---

## Step 6: Simple Caching (Index and Show)

### In `index()` method:

```php
$products = Cache::remember('products_list', 300, function () {
    return Product::with('categories')->get();
});
```

### In `show()` method:

```php
$cacheKey = 'product_' . $product->id;

$cachedProduct = Cache::remember($cacheKey, 300, function () use ($product) {
    return $product->load('categories');
});
```

### When you `store()`, `update()`, or `destroy()`:

```php
Cache::forget('products_list');
Cache::forget('product_' . $product->id);
```

---

## Summary

✅ Add `image` column to the database  
✅ Support image upload, update, and delete  
✅ Return `image_url` in the API response  
✅ Implement simple cache for better performance  

---

## Practice Challenge

- Extend product image support with multiple images using a `product_images` table  
- Allow uploading a gallery during product creation  
- Cache product galleries separately

