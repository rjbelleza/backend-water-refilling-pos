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

            return response()->json([
                'status' => 'success',
                'message' => 'Fetched categories successfully',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching categories: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching categories',
                'error' => $e->getMessage() 
            ], 500);
        }
    }


    public function store(Request $request) 
    {
        try {
            // Validate input first
            $validated = $request->validate([
                'name' => 'required|string|max:50',
            ]);

            $category = Category::where('name', $validated['name'])->first();

            if ($category && !$category->isActive) {
                // Re-enable and update the existing category
                $category->update([
                    'isActive' => true
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Category added successfully',
                    'category' => $category
                ]);
            }

            if ($category && $category->isActive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category with the same name already exists'
                ], 409);
            }
    
            // Create new category
            Category::create([
                'name' => $validated['name'],
                'isActive' => true,
            ]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'New category created',
            ], 201);
    
        } catch (\Exception $e) {
            \Log::error('Error creating category' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'Failed creating category',
            ], 500);
        }
    }

    public function disable(Category $category) 
    {
        try {
            $category->forceFill(['isActive' => false])->save();

            $category->refresh();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Category removed successfully',
                'data' => $category 
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting category: ' . $e->getMessage(), [
                'exception' => $e
            ]);
    
            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing category',
                'error' => $e->getMessage() 
            ], 500);
        }
    }
    
}
