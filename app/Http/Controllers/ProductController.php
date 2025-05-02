<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{

    public function index() {
        try {
            $products = Product::with(['category', 'user'])->where('isActive', 1)->get();

            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => $e,
                'message' => 'Error fetching products'
            ]);
        }
    } 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'category_id' => 'required|integer',
            'unit' => 'required|string'
        ]);

        try {
            $product = Product::where('name', $validated['name'])->first();

            if ($product && !$product->isActive) {
                // Re-enable and update the existing product
                $product->update([
                    'price' => $validated['price'],
                    'category_id' => $validated['category_id'],
                    'stock_quantity' => $validated['stock_quantity'],
                    'unit' => $validated['unit'],
                    'user_id' => auth()->id(),
                    'isActive' => true
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Product added successfully',
                    'product' => $product
                ]);
            }

            if ($product && $product->isActive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product with the same name already exists.'
                ], 409);
            }

            // No existing product, so create a new one
            $newProduct = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'stock_quantity' => $validated['stock_quantity'],
                'unit' => $validated['unit'],
                'user_id' => auth()->id(),
                'isActive' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'New product created.',
                'product' => $newProduct
            ]);

        } catch (\Exception $e) {
            \Log::error('Error storing product: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed adding product',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateDetails(Request $request, $id) 
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products')->ignore($id), 
                ],
                'price' => 'required',
                'category_id' => 'required|exists:categories,id',
                'unit' => 'required|string'
            ]);

            
            $product = Product::findOrFail($id);
            $product->update($validated);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating products: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error updating categories',
                'error' => $e->getMessage() 
            ], 500);
        }
    }

    public function disable(Product $product) 
    {
        try {
            $product->update(['isActive' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting product',
                'error' => $e
            ], 500);
        }
    }
}
