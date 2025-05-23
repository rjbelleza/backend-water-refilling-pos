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

            // Apply date filtering if provided
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $expenses = $query->orderBy('created_at', 'desc')
                            ->paginate($pageSize);

            return response()->json($expenses);

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
