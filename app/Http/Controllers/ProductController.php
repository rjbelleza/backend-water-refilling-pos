<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{

    public function index() {
        try {
            $products = Product::with(['user', 'category'])->where('isActive', 1)->get();

            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => $e,
                'status' => 'error',
                'message' => 'Error fetching products'
            ]);
        }
    } 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'nullable|integer',
        ]);

        $track_stock = $validated['category_id'] == 1 ? false : true;

        try {
            $product = Product::where('name', $validated['name'])->first();

            if ($product && !$product->isActive) {
                // Re-enable and update the existing product
                $product->update([
                    'price' => $validated['price'],
                    'stock_quantity' => $validated['stock_quantity'] ?? 0,
                    'category_id' => $validated['category_id'],
                    'track_stock' => $track_stock,
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
                'stock_quantity' => $validated['stock_quantity'] ?? 0,
                'category_id' => $validated['category_id'],
                'track_stock' => $track_stock,
                'user_id' => auth()->id(),
                'isActive' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'New product created',
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
            ]);

            $product = Product::findOrFail($id);

            // Check category_id and set stock-related values
            if ((int)$validated['category_id'] === 2) {
                $validated['track_stock'] = true;
            } elseif ((int)$validated['category_id'] === 1) {
                $validated['track_stock'] = false;
                $validated['stock_quantity'] = 0;
            }

            $product->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating products: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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

    public function updateStock(Request $request, Product $product) {
        try {
            $validated = $request->validate([
                'stockAction' => 'required|in:stock-in,stock-out',
                'toStock' => 'required'
            ]);

            if ($validated['stockAction'] === 'stock-out') {
                if ($product->stock_quantity < $validated['toStock']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Not enough stock to deduct'
                    ], 400);
                }

                $product->stock_quantity -= $validated['toStock'];
            } else {
                $product->stock_quantity += $validated['toStock'];
            }

            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock updated successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating stock: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed updating stock',
                'error' => $e->getMessage()
            ]);
        }
    }
}
