<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
  public function index(Request $request)
  {
        $pageSize = $request->query('pageSize', 10);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Validate date range
        if ($startDate && $endDate && $endDate < $startDate) {
            return response()->json([
                'status' => 'error',
                'message' => 'The end date cannot be earlier than the start date.'
            ], 422);
        }

        $query = Sale::with([
            'user:id,fname,lname,role',
            'customer:id,name',
            'saleProducts.product:id,name,price'
        ]);

        // Apply date filtering
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Clone the query to calculate total sales amount
        $totalSalesAmount = (clone $query)->get()->sum(function ($sale) {
            return ($sale->subtotal + $sale->additional_fee) - $sale->discount;
        });

        // Paginate the sales
        $sales = $query->orderBy('created_at', 'desc')->paginate($pageSize);

        return response()->json([
            'status' => 'success',
            'total_sales_amount' => round($totalSalesAmount, 2),
            'data' => $sales
        ]);
    }


    public function show($id)
    {
        $sale = Sale::with([
            'user:id,fname,lname,role',
            'customer:id,name',
            'saleProducts.product:id,name,price'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'additional_fee' => 'nullable|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $rawTotal = $validated['subtotal'];
            $discount = $validated['discount'] ?? 0;
            $discountType = $validated['discount_type'] ?? 'fixed';
            $additionalFee = $validated['additional_fee'] ?? 0;

            // Convert discount if type is percentage
            if ($discountType === 'percentage') {
                $discount = ($discount / 100) * $rawTotal;
            }

            $finalTotal = $rawTotal - $discount + $additionalFee;
            $change = $validated['amount_paid'] - $finalTotal;

            // Validate that amount paid is sufficient
            if ($validated['amount_paid'] < $finalTotal) {
                throw new \Exception("Insufficient payment. Required: {$finalTotal}, Paid: {$validated['amount_paid']}");
            }

            // Always insert or fetch customer, even for walk-in
           $originalName = trim($validated['customer_name'] ?? 'walk-in');

            // Try to find customer case-insensitively
            $customer = Customer::whereRaw('LOWER(name) = ?', [strtolower($originalName)])->first();

            // If not found, create a new one using the original case
            if (!$customer) {
                $customer = Customer::create(['name' => $originalName]);
            }


            // Prepare sale data
            $saleData = [
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'subtotal' => $rawTotal,
                'discount' => $discount,
                'additional_fee' => $additionalFee,
                'amount_paid' => $validated['amount_paid'],
            ];

            // Log sale data for debugging
            Log::info('Creating sale with data:', $saleData);

            // Create sale record
            $sale = Sale::create($saleData);

            // Process each product
            foreach ($validated['products'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];

                // Only track stock if track_stock is true
                if ($product->track_stock) {
                    // Check stock availability
                    if ($product->stock_quantity < $quantity) {
                        throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, Required: {$quantity}");
                    }

                    // Update product stock
                    $product->decrement('stock_quantity', $quantity);
                }

                // Create sale product record
                $sale->saleProducts()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $quantity,
                ]);
            }

            // Load relationships for response
            $sale->load([
                'user:id,fname,lname',
                'customer:id,name',
                'saleProducts.product:id,name'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sale recorded successfully',
                'data' => [
                    'sale' => $sale,
                    'total' => $finalTotal,
                    'change' => $change,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale creation failed:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Sale failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::findOrFail($id);

            // Restore stock for all products in this sale
            foreach ($sale->saleProducts as $saleProduct) {
                $product = $saleProduct->product;
                $product->increment('stock_quantity', $saleProduct->quantity);
            }

            // Delete sale products first
            $sale->saleProducts()->delete();
            
            // Delete the sale
            $sale->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sale deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale deletion failed:', [
                'error' => $e->getMessage(),
                'sale_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSalesReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $query = Sale::with(['user:id,fname,lname', 'customer:id,name']);

        if (!empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (!empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum(function ($sale) {
            return $sale->subtotal - $sale->discount + $sale->additional_fee;
        });
        $totalDiscount = $sales->sum('discount');
        $totalFees = $sales->sum('additional_fee');

        return response()->json([
            'status' => 'success',
            'data' => [
                'sales' => $sales,
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'total_discount' => $totalDiscount,
                    'total_additional_fees' => $totalFees,
                ]
            ]
        ]);
    }

    public function getDailySales(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $sales = Sale::with(['user:id,fname,lname', 'customer:id,name'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRevenue = $sales->sum(function ($sale) {
            return $sale->subtotal - $sale->discount + $sale->additional_fee;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $date,
                'sales' => $sales,
                'total_sales' => $sales->count(),
                'total_revenue' => $totalRevenue,
            ]
        ]);
    }
}
