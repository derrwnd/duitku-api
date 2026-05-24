<?php

namespace App\Http\Controllers\Api;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class RecurringTransactionController extends Controller
{
    public function index(Request $request)
    {
        $recurrings = RecurringTransaction::with([
            'wallet',
            'category'
        ])
        ->where('user_id', $request->user()->id)
        ->latest()
        ->get();

        return response()->json($recurrings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'required|date',
        ]);

        $wallet = Wallet::findOrFail($validated['wallet_id']);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized wallet'
            ], 403);
        }

        $recurring = RecurringTransaction::create([
            'user_id' => $request->user()->id,
            'is_active' => true,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Recurring transaction created',
            'data' => $recurring->load(['wallet', 'category'])
        ], 201);
    }

    public function show(
        Request $request,
        RecurringTransaction $recurringTransaction
    ) {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json(
            $recurringTransaction->load([
                'wallet',
                'category',
                'transactions'
            ])
        );
    }

    public function update(
        Request $request,
        RecurringTransaction $recurringTransaction
    ) {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'wallet_id' => 'sometimes|exists:wallets,id',
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:1',
            'note' => 'nullable|string',
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $recurringTransaction->update($validated);

        return response()->json([
            'message' => 'Recurring transaction updated',
            'data' => $recurringTransaction->load(['wallet', 'category'])
        ]);
    }

    public function destroy(
        Request $request,
        RecurringTransaction $recurringTransaction
    ) {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $recurringTransaction->delete();

        return response()->json([
            'message' => 'Recurring transaction deleted'
        ]);
    }
}
