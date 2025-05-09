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

        // Default to last year if range is not valid
        $startDate = match ($range) {
            'last_year' => now()->subYear(),
            'last_month' => now()->subMonth(),
            'last_week' => now()->subWeek(),
            'last_day' => now()->subDay(),
            default => now()->subYear(),
        };

        try {
            $totalSales = DB::table('sales')
                ->where('created_at', '>=', $startDate)
                ->sum(DB::raw('subtotal - discount'));

            $salesCount = DB::table('sales')
                ->where('created_at', '>=', $startDate)
                ->count();

            $totalExpenses = DB::table('expenses')
                ->where('created_at', '>=', $startDate)
                ->sum('amount');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_sales' => round($totalSales, 2),
                    'total_expenses' => round($totalExpenses, 2),
                    'net_profit' => round($totalSales - $totalExpenses, 2),
                    'sales_count' => $salesCount,
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

    public function getGraphData(Request $request)
    {
        $range = $request->query('range', 'last_month');

        // Define time periods and grouping based on range
        $timePeriod = match ($range) {
            'last_year' => [
                'startDate' => now()->subYear(),
                'format' => '%Y-%m', // Group by month for year view
                'interval' => '1 month',
                'label' => 'month'
            ],
            'last_month' => [
                'startDate' => now()->subMonth(),
                'format' => '%Y-%m-%d', // Group by day for month view
                'interval' => '1 day', 
                'label' => 'day'
            ],
            'last_week' => [
                'startDate' => now()->subWeek(),
                'format' => '%Y-%m-%d', // Group by day for week view
                'interval' => '1 day',
                'label' => 'day'
            ],
            'last_day' => [
                'startDate' => now()->subDay(),
                'format' => '%Y-%m-%d %H:00', // Group by hour for day view
                'interval' => '1 hour',
                'label' => 'hour'
            ],
            default => [
                'startDate' => now()->subMonth(),
                'format' => '%Y-%m-%d',
                'interval' => '1 day',
                'label' => 'day'
            ],
        };

        try {
            // Get sales data grouped by the appropriate time period
            $salesData = DB::table('sales')
                ->select(DB::raw("DATE_FORMAT(created_at, '{$timePeriod['format']}') as period"))
                ->selectRaw('SUM(subtotal - discount) as sales')
                ->where('created_at', '>=', $timePeriod['startDate'])
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Get expenses data grouped by the same time period
            $expensesData = DB::table('expenses')
                ->select(DB::raw("DATE_FORMAT(created_at, '{$timePeriod['format']}') as period"))
                ->selectRaw('SUM(amount) as expenses')
                ->where('created_at', '>=', $timePeriod['startDate'])
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Create a complete time series with all periods (including zeros for missing data)
            $periods = [];
            $currentDate = clone $timePeriod['startDate'];
            $endDate = now();

            while ($currentDate <= $endDate) {
                $period = $currentDate->format(match ($timePeriod['label']) {
                    'month' => 'Y-m',
                    'day' => 'Y-m-d',
                    'hour' => 'Y-m-d H:00',
                });

                $periodLabel = match ($timePeriod['label']) {
                    'month' => $currentDate->format('M Y'),
                    'day' => $currentDate->format('M d'),
                    'hour' => $currentDate->format('H:00'),
                };

                $periods[$period] = [
                    'period' => $period,
                    'label' => $periodLabel,
                    'sales' => 0,
                    'expenses' => 0
                ];

                // Advance to next period
                if ($timePeriod['label'] === 'month') {
                    $currentDate->addMonth();
                } else if ($timePeriod['label'] === 'day') {
                    $currentDate->addDay();
                } else if ($timePeriod['label'] === 'hour') {
                    $currentDate->addHour();
                }
            }

            // Fill in actual sales data
            foreach ($salesData as $item) {
                if (isset($periods[$item->period])) {
                    $periods[$item->period]['sales'] = round($item->sales, 2);
                }
            }

            // Fill in actual expenses data
            foreach ($expensesData as $item) {
                if (isset($periods[$item->period])) {
                    $periods[$item->period]['expenses'] = round($item->expenses, 2);
                }
            }

            // Transform to expected format
            $result = array_values($periods);
            
            // Format for ApexCharts
            $formattedData = [
                'monthlyData' => $result,
                'updatedAt' => now()->toIso8601String()
            ];

            return response()->json($formattedData);
        } catch (\Exception $e) {
            \Log::error('Graph data fetch failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch graph data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
