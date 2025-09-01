<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitController extends Controller
{
    public function getMonthlyReport(Request $request)
    {
        try {
            $perPage = $request->query('pageSize', 12); 
            $page = (int) $request->query('page', 1);

            $year = (int) $request->query('year', date('Y'));

            // Build all 12 months of the given year
            $allMonths = collect();
            for ($month = 1; $month <= 12; $month++) {
                $allMonths->push(sprintf('%d-%02d', $year, $month));
            }

            // Fetch sales grouped by month
            $sales = DB::table('sales')
                ->selectRaw("TO_CHAR(created_at, '%Y-%m') as month, SUM(subtotal - discount) as total_sales")
                ->whereYear('created_at', '=', $year)
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            // Fetch expenses grouped by month
            $expenses = DB::table('expenses')
                ->selectRaw("TO_CHAR(created_at, '%Y-%m') as month, SUM(amount) as total_expenses")
                ->whereYear('created_at', '=', $year)
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            // Merge sales and expenses with all months
            $merged = $allMonths->map(function ($month) use ($sales, $expenses) {
                $sale = $sales->get($month);
                $expense = $expenses->get($month);

                $totalSales = $sale ? (float) $sale->total_sales : 0;
                $totalExpenses = $expense ? (float) $expense->total_expenses : 0;

                return [
                    'month' => $month,
                    'total_sales' => $totalSales,
                    'total_expenses' => $totalExpenses,
                    'net_profit' => $totalSales - $totalExpenses,
                ];
            });

            // Calculate yearly totals
            $yearTotalSales = $merged->sum('total_sales');
            $yearTotalExpenses = $merged->sum('total_expenses');
            $yearNetProfit = $merged->sum('net_profit');

            // Manual pagination
            $offset = ($page - 1) * $perPage;
            $paginated = $merged->slice($offset, $perPage)->values();

            return response()->json([
                'data' => $paginated,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $merged->count(),
                'last_page' => ceil($merged->count() / $perPage),
                'total_sales' => $yearTotalSales,
                'total_expenses' => $yearTotalExpenses,
                'net_profit' => $yearNetProfit,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching records: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching records',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
