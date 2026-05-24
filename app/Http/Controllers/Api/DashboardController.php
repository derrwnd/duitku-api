<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now();

        // Total saldo dari semua wallet
        $totalBalance = Wallet::where('user_id', $userId)
            ->sum('balance');

        // Total pemasukan bulan ini
        $totalIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        // Total pengeluaran bulan ini
        $totalExpense = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        // 10 transaksi terbaru
        $recentTransactions = Transaction::with([
            'wallet',
            'category'
        ])
        ->where('user_id', $userId)
        ->latest('transaction_date')
        ->limit(10)
        ->get();

        // Daftar wallet
        $wallets = Wallet::where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'total_balance' => (float) $totalBalance,
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'month' => $now->month,
            'year' => $now->year,
            'recent_transactions' => $recentTransactions,
            'wallets' => $wallets,
        ]);
    }
}
