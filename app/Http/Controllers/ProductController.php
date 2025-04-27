<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{

    public function index() {
        try {
            $products = Product::with(['category', 'user'])->get();

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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|unique:products',
                'price' => 'required',
                'category_id' => 'required|exists:categories,id',
                'stock_quantity' => 'required|integer|min:1'
            ]);

            Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'stock_quantity' => $validated['stock_quantity'],
                'user_id' => auth()->id(),
                'isActive' => true,
            ]);

            return response()->json([
                'message' => 'Product added successfully'
            ], 201);
        } catch (\Exception $e) {
            \Log::error($e);
            \Log::info($request);
            return response()->json([
                'error' => $e,
                'message' => 'Error adding product'
            ]);
        }
    }
}
