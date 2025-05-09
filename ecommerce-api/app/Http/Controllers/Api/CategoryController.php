<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all categories
        $categories = Category::all();

        // Return the categories as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate the request name, slug, description, parent_id, is_active
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean'
        ]);

        // generete slug = name with separator '-'
        $slug = Str::slug($request->name, '-');

        // check if slug is unique
        $count = Category::where('slug', $slug)->count(); // > 0 
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        // create a new category
        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'is_active' => $request->is_active ?? true,
        ]);
        // Return the category as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
        $category->load(['parent', 'children']);
        // Return the category as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Category retrieved successfully',
            'data' => $category
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //validate the request name, slug, description, parent_id, is_active
        // Validate incoming request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->has('name') && $request->name != $category->name) {
            // Generate slug from name
            $slug = Str::slug($request->name, '-');
            // Check if slug is unique
            $count = Category::where('slug', $slug)
                ->where('id', '!=', $category->id)->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            // Update the category name and slug
            $category->name = $request->name;
            $category->slug = $slug;
        }

        if (
            $request->has('parent_id')
            && $request->parent_id != $category->parent_id
        ) {
            $category->parent_id = $request->parent_id;
        }

        // Update the category with the validated data
        if ($request->has('description')) {
            $category->description = $request->description;
        }
        if ($request->has('is_active')) {
            $category->is_active = $request->is_active;
        }

        // Save the updated category
        $category->save();
        // Return the updated category as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // tech -> pc -> laptop
        // Check if the category has any children
        foreach ($category->children as $child) {
            $child->parent_id = $category->parent_id; // null 
            $child->save();
        }
        $category->delete(); // tech
        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
    /**
     * Get all products in a category
     */
    public function products(Category $category)
    {
        $category->load('products');
        // Return the category as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Products of Category retrieved successfully',
            'data' => $category->products
        ]);
    }
}
