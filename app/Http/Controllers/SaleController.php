<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->query('pageSize', 10);

        $sales = Sale::with([
            'user:id,fname,lname,role', // cashier who created it
            'saleProducts.product:id,name,price,unit' // products involved
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($pageSize);

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
        ]);
    
        DB::beginTransaction();
    
        try {
            $rawTotal = 0;
    
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer' => $validated['customer_name'],
                'total_amount' => 0, // to be updated later
                'discount' => 0,     // to be updated later
            ]);
    
            foreach ($validated['products'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
    
                if ($product->stock_quantity < $quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }
    
                $product->stock_quantity -= $quantity;
                $product->save();
    
                $itemSubtotal = $product->price * $quantity;
                $rawTotal += $itemSubtotal;
    
                $sale->saleProducts()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ]);
            }
    
            // Calculate total discount
            $discount = $validated['discount'] ?? 0;
            $discountType = $validated['discount_type'] ?? 'fixed';
    
            if ($discountType === 'percentage') {
                $discount = ($discount / 100) * $rawTotal;
            }
    
            $finalTotal = $rawTotal - $discount;
    
            $sale->update([
                'discount' => $discount,
                'total_amount' => $finalTotal,
            ]);
    
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
