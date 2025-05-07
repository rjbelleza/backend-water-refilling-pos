<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getSummary(Request $request)
    {
        $range = $request->query('range');

        // Default to last day if range is not valid
        $startDate = match ($range) {
            'last_year' => now()->subYear(),
            'last_month' => now()->subMonth(),
            'last_week' => now()->subWeek(),
            'last_day' => now()->subDay(),
            default => now()->subDay(),
        };

        try {
            $totalSales = DB::table('sales')
                ->where('created_at', '>=', $startDate)
                ->sum(DB::raw('subtotal - discount'));

            $totalExpenses = DB::table('expenses')
                ->where('created_at', '>=', $startDate)
                ->sum('amount');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_sales' => round($totalSales, 2),
                    'total_expenses' => round($totalExpenses, 2),
                    'net_profit' => round($totalSales - $totalExpenses, 2),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Summary fetch failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
