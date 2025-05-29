<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(Request $request) 
    {
        try {
            $pageSize = $request->query('pageSize', 10); 
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $query = Expense::with(['user']);

            // Validate date range
            if ($startDate && $endDate && $endDate < $startDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The end date cannot be earlier than the start date.'
                ], 422);
            }

            // Apply date filtering
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // Clone query to calculate total amount
            $totalExpenses = (clone $query)->sum('amount');

            // Get paginated result
            $expenses = $query->orderBy('created_at', 'desc')
                            ->paginate($pageSize);

            return response()->json([
                'status' => 'success',
                'total_expenses' => round($totalExpenses, 2),
                'data' => $expenses
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching expenses: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching expenses',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'description' => 'required|string',
                'amount' => 'required'
            ]);

            $newExpense = Expense::create([
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'user_id' => auth()->id(),
                'created_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Added expense successfully',
                'expense' => $newExpense
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adding expense: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching expenses'
            ]);
        }
    }
}
