<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallets = Wallet::where(
            'user_id',
            $request->user()->id
        )->latest()->get();

        return response()->json($wallets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string',
            'balance' => 'nullable|numeric',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $wallet = Wallet::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json([
            'message' => 'Wallet created',
            'data' => $wallet
        ], 201);
    }

    public function show(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($wallet);
    }

    public function update(
        Request $request,
        Wallet $wallet
    ) {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string',
            'balance' => 'sometimes|numeric',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $wallet->update($validated);

        return response()->json([
            'message' => 'Wallet updated',
            'data' => $wallet
        ]);
    }

    public function destroy(
        Request $request,
        Wallet $wallet
    ) {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $wallet->delete();

        return response()->json([
            'message' => 'Wallet deleted'
        ]);
    }
}