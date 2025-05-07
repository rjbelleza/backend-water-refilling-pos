<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitController extends Controller
{
    public function getMonthlyReport(Request $request)
    {
        try {
            $perPage = (int) $request->query('pageSize', 10);
            $page = (int) $request->query('page', 1);

            // Fetch sales grouped by month
            $sales = DB::table('sales')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(subtotal - discount) as total_sales')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();

            // Fetch expenses grouped by month
            $expenses = DB::table('expenses')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total_expenses')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();

            // Merge sales and expenses by month
            $merged = $sales->map(function ($sale) use ($expenses) {
                $expense = $expenses->firstWhere('month', $sale->month);
                $totalExpenses = $expense->total_expenses ?? 0;

                return [
                    'month' => $sale->month,
                    'total_sales' => (float) $sale->total_sales,
                    'total_expenses' => (float) $totalExpenses,
                    'net_profit' => (float) $sale->total_sales - $totalExpenses,
                ];
            });

            // Manual pagination
            $offset = ($page - 1) * $perPage;
            $paginated = $merged->slice($offset, $perPage)->values();

            return response()->json([
                'data' => $paginated,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $merged->count(),
                'last_page' => ceil($merged->count() / $perPage),
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
