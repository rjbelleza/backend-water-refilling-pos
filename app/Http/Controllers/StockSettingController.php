<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\StockSetting;
use App\Models\Product;

class StockSettingController extends Controller
{
    public function updateThreshold(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        try {
            $setting = StockSetting::first();

            if ($setting) {
                $setting->update([
                    'low_stock_threshold' => $request->low_stock_threshold,
                ]);
            } else {
                StockSetting::create([
                    'low_stock_threshold' => $request->low_stock_threshold,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock threshold updated successfully.',
                'threshold' => $request->low_stock_threshold
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update stock threshold: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the stock threshold.'
            ], 500);
        }
    }

    public function lowStockProducts()
    {
        try {
            $threshold = StockSetting::value('low_stock_threshold') ?? 0;

            $products = Product::where('track_stock', true)
                ->where('stock_quantity', '<=', $threshold)
                ->where('isActive', true)
                ->get();

            return response()->json([
                'success' => true,
                'threshold' => $threshold,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch low stock products: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch low stock products.',
            ], 500);
        }
    }
}
