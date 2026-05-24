<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with([
            'wallet',
            'category'
        ])
        ->where('user_id', $request->user()->id);

        // Filter berdasarkan tipe transaksi
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter berdasarkan wallet
        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }

        // Filter berdasarkan tanggal mulai
        if ($request->filled('start_date')) {
            $query->where(
                'transaction_date',
                '>=',
                $request->start_date
            );
        }

        // Filter berdasarkan tanggal akhir
        if ($request->filled('end_date')) {
            $query->where(
                'transaction_date',
                '<=',
                $request->end_date
            );
        }

        // Pencarian berdasarkan catatan
        if ($request->filled('search')) {
            $query->where(
                'note',
                'ilike',
                '%' . $request->search . '%'
            );
        }

        // Sorting (default: terbaru)
        $sortBy = $request->query('sort_by', 'transaction_date');
        $sortOrder = $request->query('sort_order', 'desc');

        $allowedSorts = [
            'transaction_date',
            'amount',
            'created_at'
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('transaction_date');
        }

        // Pagination (default 20 per halaman)
        $perPage = $request->query('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:income,expense',
            'note' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $wallet = Wallet::findOrFail(
            $validated['wallet_id']
        );

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized wallet'
            ], 403);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        // UPDATE BALANCE
        if ($validated['type'] === 'income') {
            $wallet->balance += $validated['amount'];
        } else {
            $wallet->balance -= $validated['amount'];
        }

        $wallet->save();

        return response()->json([
            'message' => 'Transaction created',
            'data' => $transaction->load(['wallet', 'category'])
        ], 201);
    }

    public function show(
        Request $request,
        Transaction $transaction
    ) {
        if (
            $transaction->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json(
            $transaction->load([
                'wallet',
                'category'
            ])
        );
    }

    public function update(
        Request $request,
        Transaction $transaction
    ) {
        if (
            $transaction->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'note' => 'nullable|string',
            'transaction_date' => 'sometimes|date',
        ]);

        // Jika amount berubah, update balance wallet
        if (isset($validated['amount'])) {
            $wallet = $transaction->wallet;
            $oldAmount = $transaction->amount;
            $newAmount = $validated['amount'];

            // Rollback balance lama
            if ($transaction->type === 'income') {
                $wallet->balance -= $oldAmount;
            } else {
                $wallet->balance += $oldAmount;
            }

            // Apply balance baru
            if ($transaction->type === 'income') {
                $wallet->balance += $newAmount;
            } else {
                $wallet->balance -= $newAmount;
            }

            $wallet->save();
        }

        $transaction->update($validated);

        return response()->json([
            'message' => 'Transaction updated',
            'data' => $transaction->load(['wallet', 'category'])
        ]);
    }

    public function destroy(
        Request $request,
        Transaction $transaction
    ) {
        if (
            $transaction->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $wallet = $transaction->wallet;

        // BALANCE ROLLBACK
        if ($transaction->type === 'income') {
            $wallet->balance -= $transaction->amount;
        } else {
            $wallet->balance += $transaction->amount;
        }

        $wallet->save();

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted'
        ]);
    }
}