<?php

namespace App\Http\Controllers\Api;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SavingsGoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = SavingsGoal::with('wallet')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($goal) {
                $goal->percentage = $goal->target_amount > 0
                    ? round(($goal->current_amount / $goal->target_amount) * 100, 1)
                    : 0;
                $goal->is_completed = $goal->current_amount >= $goal->target_amount;

                return $goal;
            });

        return response()->json($goals);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'wallet_id' => 'nullable|exists:wallets,id',
            'deadline' => 'nullable|date|after:today',
        ]);

        $goal = SavingsGoal::create([
            'user_id' => $request->user()->id,
            'current_amount' => $validated['current_amount'] ?? 0,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Savings goal created',
            'data' => $goal
        ], 201);
    }

    public function show(Request $request, SavingsGoal $savingsGoal)
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $savingsGoal->percentage = $savingsGoal->target_amount > 0
            ? round(($savingsGoal->current_amount / $savingsGoal->target_amount) * 100, 1)
            : 0;
        $savingsGoal->is_completed = $savingsGoal->current_amount >= $savingsGoal->target_amount;

        return response()->json($savingsGoal->load('wallet'));
    }

    public function update(
        Request $request,
        SavingsGoal $savingsGoal
    ) {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:100',
            'target_amount' => 'sometimes|numeric|min:1',
            'current_amount' => 'sometimes|numeric|min:0',
            'wallet_id' => 'nullable|exists:wallets,id',
            'deadline' => 'nullable|date',
        ]);

        $savingsGoal->update($validated);

        return response()->json([
            'message' => 'Savings goal updated',
            'data' => $savingsGoal
        ]);
    }

    public function destroy(
        Request $request,
        SavingsGoal $savingsGoal
    ) {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $savingsGoal->delete();

        return response()->json([
            'message' => 'Savings goal deleted'
        ]);
    }
}
