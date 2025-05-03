<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'customer' => 'nullable|string|max:50',
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.quantity' => 'required|integer|min:1',
        'products.*.discount' => 'nullable|numeric|min:0', // per item
    ]);

    DB::beginTransaction();

    try {
        $totalAmount = 0;

        // 1. Create Sale Record
        $sale = Sale::create([
            'user_id' => auth()->id(),
            'customer' => $validated['customer'] ?? 'walk-in',
            'total_amount' => 0, // updated later
        ]);

        foreach ($validated['products'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantity = $item['quantity'];
            $discount = $item['discount'] ?? 0;

            if ($product->stock_quantity < $quantity) {
                throw new \Exception("Insufficient stock for {$product->name}");
            }

            // 2. Deduct Stock
            $product->stock_quantity -= $quantity;
            $product->save();

            // 3. Calculate Subtotal
            $rawTotal = $product->price * $quantity;
            $subtotal = $rawTotal - $discount;

            // 4. Store Sale Item
            $sale->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'discount' => $discount,
                'subtotal' => $subtotal,
            ]);

            $totalAmount += $subtotal;
        }

        // 5. Update Sale Total
        $sale->total_amount = $totalAmount;
        $sale->save();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Sale recorded successfully',
            'sale_id' => $sale->id
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Sale error: ' . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'Sale failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
