<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index() 
    {
        try {
            $categories = Category::select('id', 'name')
                                ->where('isActive', 1) 
                                ->get();

            return response()->json([
                'status' => 'success',
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
    
            // Check if the category already exists
            $existingCategory = Category::where('name', $validated['name'])
                                        ->where('isActive', true)
                                        ->first();
    
            if ($existingCategory) {
                return response()->json([
                    'message' => 'Category already exists'
                ], 409); 
            }
    
            // Create new category
            Category::create([
                'name' => $validated['name'],
                'isActive' => true,
            ]);
    
            return response()->json([
                'message' => 'Category created successfully',
            ], 201);
    
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'An error occurred while creating the category.',
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

    public function disable(Category $category) 
    {
        try {
            $category->forceFill(['isActive' => false])->save();

            $category->refresh();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Category disabled successfully',
                'data' => $category 
            ]);

        } catch (\Exception $e) {
            \Log::error('Error disabling category: ' . $e->getMessage(), [
                'exception' => $e
            ]);
    
            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Error disabling category',
                'error' => $e->getMessage() 
            ], 500);
        }
    }
    
}
