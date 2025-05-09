<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // eager loading categories wiht products caching
        // cache for 300 seconds (5 minutes)
        $products = Cache::remember('products', 300, function () {
            return Product::with('categories')->get();
        });
        /*
         [
            {
                "id": 1,
                "name": "Product 1",
                "slug": "product-1",
                "description": "Product 1 description",
                "price": 10.00,
                "stock": 10,
                "sku": "SKU-001",
                "is_active": true,
                "categories": [
                    {
                        "id": 1,
                        "name": "Category 1",
                        "slug": "category-1"
                    },
                    {
                        "id": 2,
                        "name": "Category 2",
                        "slug": "category-2"
                    }
                ]
            }
        ] 
         */

        // is active products only (default)
        //$products = Product::where('is_active', true)->get();

        // with global scope
        //$products = Product::all(); // all records from the products table filtered by global scope is active

        // without global scope
        //$products = Product::withoutGlobalScope('active')->get(); // all records from the products table without global scope



        // get products between 5 and 40 usage of local scope
        // $products = Product::priceBetween(5, 40)->get(); 

        // formatted name attribute (accessor)
        // $product = Product::first();
        // return $product->formatted_name;

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // validate the request
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
            'sku' => 'required|string|max:255|unique:products',
            'is_active' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // 2MB
        ]);
        // check if image uploaded
        if ($request->hasFile('image')) {
            // store the image with random name
            //$data['image'] = $request->file('image')->store('products', 'public');
            // store the image with product slug
            $data['image'] = $request->file('image')->storeAs('products', $data['slug'], 'public');
        }
        // create the product
        $product = Product::create($data);



        // attach categories
        if ($request->has('categories')) {
            $product->categories()->attach($data['categories']);
        }
        $product->load('categories');
        /* 
        {
            "id": 1,
            "name": "Product 1",
            "slug": "product-1",
            "description": "Product 1 description",
            "price": 10.00,
            "stock": 10,
            "sku": "SKU-001",
            "is_active": true,
            "categories": [
                {
                    "id": 1,
                    "name": "Category 1",
                    "slug": "category-1"
                },
                {
                    "id": 2,
                    "name": "Category 2",
                    "slug": "category-2"
                }
            ]
         */

        Cache::forget('products'); // clear the cache
        // return the product
        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // eager load categories with product caching
        $productCached = Cache::remember('product_' . $product->id, 300, function () use ($product) {
            return $product->load('categories');
        });
        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => $productCached
        ], 200);
        /*
        {
            "id": 1,
            "name": "Product 1",
            "slug": "product-1",
            "description": "Product 1 description",
            "price": 10.00,
            "stock": 10,
            "sku": "SKU-001",
            "is_active": true,

            "categories": [
                {
                    "id": 1,
                    "name": "Category 1",
                    "slug": "category-1"
                },
                {
                    "id": 2,
                    "name": "Category 2",
                    "slug": "category-2"
                }
            ]
         */
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // validate the request for update and merge with existing data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $product->id,
            'is_active' => 'sometimes|boolean',
            'categories' => 'sometimes|array',
            'categories.*' => 'sometimes|exists:categories,id',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048' // 2MB
        ]);

        if ($request->has('name')) {
            $product->name = $request->name;
            $product->slug = Str::slug($request->name, '-');
        }
        if ($request->has('description')) $product->description = $request->description;
        if ($request->has('price')) $product->price = $request->price;
        if ($request->has('stock')) $product->stock = $request->stock;
        if ($request->has('sku')) $product->sku = $request->sku;
        if ($request->has('is_active')) $product->is_active = $request->is_active;

        // check if image uploaded
        if ($request->hasFile('image')) {
            // store the image with random name
            // $product->image = $request->file('image')->store('products', 'public');
            // store the image with product slug
            $product->image = $request->file('image')->storeAs('products', $product->slug, 'public');
        }
        // update the product
        $product->save();

        // sync categories
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        // load the categories
        $product->load('categories');
        /*
        {
            "id": 1,
            "name": "Product 1",
            "slug": "product-1",
            "description": "Product 1 description",
            "price": 10.00,
            "stock": 10,
            "sku": "SKU-001",
            "is_active": true,
            "categories": [
                {
                    "id": 1,
                    "name": "Category 1",
                    "slug": "category-1"
                },
                {
                    "id": 2,
                    "name": "Category 2",
                    "slug": "category-2"
                }
            ]
         */

        // clear the cache
        Cache::forget('product_' . $product->id);
        Cache::forget('products'); // clear the cache

        // return the product
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // if the product has image, delete it
        if ($product->image) {
            // delete the image from storage
            // $product->image has the root path
            // $product->image = 'products/1.jpg'
            Storage::disk('public')->delete($product->image);
        }

        // clear the cache
        Cache::forget('product_' . $product->id);
        Cache::forget('products'); // clear the cache
        
        $product->delete();



        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ], 200);
    }

    // undo soft delete
    public function undoDelete(Request $request, Product $product)
    {
        if ($request->user()->hasRole('admin')) {
            $product->restore();
            return response()->json([
                'success' => true,
                'message' => 'Product restored successfully',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action',
        ], 403);
    }
    // permanent delete
    public function permanentDelete(Request $request, Product $product)
    {
        if ($request->user()->hasRole('admin')) {
            $product->forceDelete();
            return response()->json([
                'success' => true,
                'message' => 'Product permanently deleted successfully',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action',
        ], 403);
    }

    // index of admin products
    public function adminIndex(Request $request)
    {
        // get all products (default)

        $products = Product::withTrashed()->get();

        if ($request->user()->hasRole('admin')) {
            $products = Product::withTrashed()->get();
            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action',
        ], 403);
    }

    // filter products by name, description, price
    public function filter(Request $request)
    {
        $products = Product::query()
            ->when($request->price_min, fn($query) =>
            $query->where('price', '>=', $request->price_min))
            ->when($request->price_max, fn($query) =>
            $query->where('price', '<=', $request->price_max))
            ->when($request->q, function ($query) use ($request) {
                $query->where(
                    fn($query) =>
                    $query->where('name', 'like', "%{$request->q}%")
                        ->orWhere('description', 'like', "%{$request->q}%")
                );
            })->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }
}
