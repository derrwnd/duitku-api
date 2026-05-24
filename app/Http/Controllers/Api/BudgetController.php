<?php

namespace App\Http\Controllers\Api;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $budgets = Budget::with('category')
            ->where('user_id', $request->user()->id)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->map(function ($budget) use ($month, $year) {
                // Hitung pengeluaran aktual per kategori
                $spent = Transaction::where('user_id', $budget->user_id)
                    ->where('category_id', $budget->category_id)
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year)
                    ->sum('amount');

                $budget->spent = (float) $spent;
                $budget->remaining = (float) ($budget->amount - $spent);
                $budget->percentage = $budget->amount > 0
                    ? round(($spent / $budget->amount) * 100, 1)
                    : 0;
                $budget->is_over_budget = $spent > $budget->amount;

                return $budget;
            });

        return response()->json($budgets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        // Cek duplikasi budget untuk kategori di bulan yang sama
        $exists = Budget::where('user_id', $request->user()->id)
            ->where('category_id', $validated['category_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Budget untuk kategori ini di bulan tersebut sudah ada'
            ], 422);
        }

        $budget = Budget::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Budget created',
            'data' => $budget->load('category')
        ], 201);
    }

    public function show(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Hitung pengeluaran aktual
        $spent = Transaction::where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $budget->month)
            ->whereYear('transaction_date', $budget->year)
            ->sum('amount');

        $budget->spent = (float) $spent;
        $budget->remaining = (float) ($budget->amount - $spent);
        $budget->percentage = $budget->amount > 0
            ? round(($spent / $budget->amount) * 100, 1)
            : 0;
        $budget->is_over_budget = $spent > $budget->amount;

        return response()->json($budget->load('category'));
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'month' => 'sometimes|integer|between:1,12',
            'year' => 'sometimes|integer|min:2020',
        ]);

        $budget->update($validated);

        return response()->json([
            'message' => 'Budget updated',
            'data' => $budget->load('category')
        ]);
    }

    public function destroy(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $budget->delete();

        return response()->json([
            'message' => 'Budget deleted'
        ]);
    }
}
