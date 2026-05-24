<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Statistik Bulanan — Pemasukan & Pengeluaran per bulan (12 bulan terakhir)
     */
    public function monthly(Request $request)
    {
        $userId = $request->user()->id;
        $year = $request->query('year', Carbon::now()->year);

        $monthly = Transaction::where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->selectRaw("
                EXTRACT(MONTH FROM transaction_date) as month,
                type,
                SUM(amount) as total
            ")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        // Format: array of 12 months with income & expense
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $income = $monthly
                ->where('month', $m)
                ->where('type', 'income')
                ->first();
            $expense = $monthly
                ->where('month', $m)
                ->where('type', 'expense')
                ->first();

            $result[] = [
                'month' => $m,
                'income' => (float) ($income->total ?? 0),
                'expense' => (float) ($expense->total ?? 0),
            ];
        }

        return response()->json([
            'year' => (int) $year,
            'data' => $result
        ]);
    }

    /**
     * Statistik Mingguan — Pengeluaran per hari dalam 7 hari terakhir
     */
    public function weekly(Request $request)
    {
        $userId = $request->user()->id;
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $daily = Transaction::where('user_id', $userId)
            ->whereBetween('transaction_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->selectRaw("
                transaction_date,
                type,
                SUM(amount) as total
            ")
            ->groupBy('transaction_date', 'type')
            ->orderBy('transaction_date')
            ->get();

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays(6 - $i)->toDateString();

            $income = $daily
                ->where('transaction_date', $date)
                ->where('type', 'income')
                ->first();
            $expense = $daily
                ->where('transaction_date', $date)
                ->where('type', 'expense')
                ->first();

            $result[] = [
                'date' => $date,
                'day' => Carbon::parse($date)->translatedFormat('D'),
                'income' => (float) ($income->total ?? 0),
                'expense' => (float) ($expense->total ?? 0),
            ];
        }

        return response()->json($result);
    }

    /**
     * Statistik per Kategori — Diagram pie pengeluaran/pemasukan per kategori
     */
    public function category(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->query('type', 'expense');
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $stats = Transaction::where('user_id', $userId)
            ->where('type', $type)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->with('category')
            ->selectRaw("
                category_id,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $stats->sum('total');

        $result = $stats->map(function ($item) use ($grandTotal) {
            return [
                'category_id' => $item->category_id,
                'category' => $item->category,
                'total' => (float) $item->total,
                'count' => $item->count,
                'percentage' => $grandTotal > 0
                    ? round(($item->total / $grandTotal) * 100, 1)
                    : 0,
            ];
        });

        return response()->json([
            'type' => $type,
            'month' => (int) $month,
            'year' => (int) $year,
            'grand_total' => (float) $grandTotal,
            'data' => $result
        ]);
    }
}
