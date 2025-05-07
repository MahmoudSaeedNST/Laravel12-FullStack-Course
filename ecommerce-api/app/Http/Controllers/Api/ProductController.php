<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // get all products (default)
        $products = Product::all();

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
            'is_active' => 'boolean'
        ]);
        // create the product
        $product = Product::create($data);
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
        //
        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
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
            'is_active' => 'sometimes|boolean'
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

        // update the product
        $product->save();

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
        // delete the product vs soft delete
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
        ->when($request->q, function($query) use ($request){
            $query->where(fn($query) =>
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
