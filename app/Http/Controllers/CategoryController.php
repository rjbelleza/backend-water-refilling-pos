<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index() 
    {
        try {
            $categories = Category::select('id', 'name')->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => $e,
                'message' => 'Error fetching categories'
            ], 500);
        }
    }

    public function store(Request $request) 
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|unique:categories',
            ]);
    
            Category::create([
                'name' => $validated['name'],
            ]);
    
            return response()->json([
                'message' => 'Category created successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Category name already exist!',
            ], 500);
        }
    }


    public function destroy(Category $category) {
        try {
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'message' => 'Error deleting category',
                'error' => $e
            ], 500);
        }
    }
}
