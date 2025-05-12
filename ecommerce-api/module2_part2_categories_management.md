
# Module 2 - Part 2: Categories Management

## Introduction

Categories help organize products in an e-commerce system, making it easier for customers to find what they're looking for.  
A well-designed category system allows for hierarchical organization, where categories can have parent-child relationships.  
This creates a tree-like structure that mimics how people naturally categorize items.

---

## Let's Build: Categories Management

### Step 1: Create Database Migration for Categories

```bash
php artisan make:migration create_categories_table
```

Update the migration file:

```php
$table->id();
$table->string('name');
$table->string('slug')->unique();
$table->text('description')->nullable();
$table->boolean('is_active')->default(true);
$table->unsignedBigInteger('parent_id')->nullable();

$table->foreign('parent_id')
      ->references('id')
      ->on('categories')
      ->onDelete('set null');

$table->timestamps();
```

Run the migration:

```bash
php artisan migrate
```

---

### Step 2: Create Category Model

```bash
php artisan make:model Category
```

Inside `app/Models/Category.php`:

```php
protected $fillable = [
    'name',
    'slug',
    'description',
    'is_active',
    'parent_id'
];

public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}

public function activeChildren()
{
    return $this->children()->where('is_active', true);
}

public function isTopLevel()
{
    return is_null($this->parent_id);
}
```

---

### Step 3: Create Category Controller

```bash
php artisan make:controller Api/CategoryController --resource
```

Implement CRUD logic inside `CategoryController.php`, including:
- Slug generation using `Str::slug()`
- Preventing categories from being their own parent
- Unique slug handling
- Relationship loading (`parent`, `children`)
- Graceful re-parenting of children on delete

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        // Get all active categories
        $categories = Category::where('is_active', true)->get();
        
        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id', // Make sure parent exists if provided
        ]);

        // Generate a slug from the name (for URLs)
        $slug = Str::slug($request->name);
        
        // Check if slug already exists and make it unique
        $count = Category::where('slug', $slug)->count();
        if ($count > 0) {
            // Append a number to make slug unique
            $slug = $slug . '-' . ($count + 1);
        }

        // Create the new category
        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'is_active' => true, // New categories are active by default
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201); // 201 Created status code
    }

    /**
     * Display a specific category
     */
    public function show(Category $category)
    {
        // Load the parent and children relationships
        $category->load(['parent', 'children']);
        
        return response()->json([
            'category' => $category
        ]);
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Prevent category from being its own parent
        if ($request->has('parent_id') && $request->parent_id == $category->id) {
            return response()->json([
                'message' => 'A category cannot be its own parent',
                'errors' => ['parent_id' => ['Invalid parent category']]
            ], 422); // 422 Unprocessable Entity
        }

        // Update slug only if name is changing
        if ($request->has('name') && $request->name !== $category->name) {
            $slug = Str::slug($request->name);
            
            // Check if slug already exists and make it unique
            $count = Category::where('slug', $slug)
                            ->where('id', '!=', $category->id)
                            ->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            
            $category->slug = $slug;
        }

        // Update other fields if provided
        if ($request->has('name')) $category->name = $request->name;
        if ($request->has('description')) $category->description = $request->description;
        if ($request->has('is_active')) $category->is_active = $request->is_active;
        if ($request->has('parent_id')) $category->parent_id = $request->parent_id;

        // Save changes
        $category->save();

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        // Before deleting, handle child categories
        foreach ($category->children as $child) {
            // Move children to the parent of the category being deleted
            // This maintains the hierarchy
            $child->parent_id = $category->parent_id;
            $child->save();
        }

        // Now delete the category
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
```
---

### Step 4: Set Up API Routes

Inside `routes/api.php`:

```php
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', 'permission:create categories'])->group(function () {
    
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
});
```

---

## What We've Learned

- Created a database schema for hierarchical categories.
- Built a model with self-referential relationships (parent-child).
- Implemented a controller with full CRUD operations.
- Added validation, slug logic, and safe deletion handling.
- Configured API routes with role-based access control.
