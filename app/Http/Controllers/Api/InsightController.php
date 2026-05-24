<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class InsightController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $insights = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Total pengeluaran bulan ini vs bulan lalu
        |--------------------------------------------------------------------------
        */
        $expenseThisMonth = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        $lastMonth = Carbon::now()->subMonth();
        $expenseLastMonth = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $lastMonth->month)
            ->whereYear('transaction_date', $lastMonth->year)
            ->sum('amount');

        if ($expenseLastMonth > 0) {
            $changePercent = round(
                (($expenseThisMonth - $expenseLastMonth) / $expenseLastMonth) * 100,
                1
            );

            if ($changePercent > 0) {
                $insights[] = [
                    'type' => 'expense_trend',
                    'icon' => 'trending_up',
                    'color' => '#EF4444',
                    'title' => 'Pengeluaran Naik',
                    'message' => 'Pengeluaran bulan ini naik ' . $changePercent . '% dibanding bulan lalu.',
                ];
            } elseif ($changePercent < 0) {
                $insights[] = [
                    'type' => 'expense_trend',
                    'icon' => 'trending_down',
                    'color' => '#10B981',
                    'title' => 'Pengeluaran Turun',
                    'message' => 'Pengeluaran bulan ini turun ' . abs($changePercent) . '% dibanding bulan lalu. Bagus!',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Kategori pengeluaran terbesar bulan ini
        |--------------------------------------------------------------------------
        */
        $topCategory = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        if ($topCategory && $topCategory->category) {
            $insights[] = [
                'type' => 'top_category',
                'icon' => $topCategory->category->icon ?? 'category',
                'color' => $topCategory->category->color ?? '#F97316',
                'title' => 'Pengeluaran Terbesar',
                'message' => 'Pengeluaran terbesar bulan ini ada pada kategori ' . $topCategory->category->name . ' sebesar Rp' . number_format($topCategory->total, 0, ',', '.') . '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Perbandingan pemasukan vs pengeluaran
        |--------------------------------------------------------------------------
        */
        $incomeThisMonth = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        if ($incomeThisMonth > 0 && $expenseThisMonth > 0) {
            $ratio = round(($expenseThisMonth / $incomeThisMonth) * 100, 1);

            if ($ratio > 90) {
                $insights[] = [
                    'type' => 'balance_warning',
                    'icon' => 'warning',
                    'color' => '#EF4444',
                    'title' => 'Peringatan Saldo',
                    'message' => 'Pengeluaran sudah mencapai ' . $ratio . '% dari pemasukan bulan ini. Hati-hati!',
                ];
            } elseif ($ratio > 70) {
                $insights[] = [
                    'type' => 'balance_caution',
                    'icon' => 'info',
                    'color' => '#F59E0B',
                    'title' => 'Perhatian',
                    'message' => 'Pengeluaran sudah ' . $ratio . '% dari pemasukan bulan ini.',
                ];
            } else {
                $insights[] = [
                    'type' => 'balance_healthy',
                    'icon' => 'check_circle',
                    'color' => '#10B981',
                    'title' => 'Keuangan Sehat',
                    'message' => 'Pengeluaran baru ' . $ratio . '% dari pemasukan. Tetap hemat!',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Hari dengan pengeluaran terbanyak minggu ini
        |--------------------------------------------------------------------------
        */
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $topDay = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString()
            ])
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->groupBy('transaction_date')
            ->orderByDesc('total')
            ->first();

        if ($topDay) {
            $dayName = Carbon::parse($topDay->transaction_date)
                ->translatedFormat('l, d M');

            $insights[] = [
                'type' => 'top_spending_day',
                'icon' => 'calendar_today',
                'color' => '#8B5CF6',
                'title' => 'Hari Paling Boros',
                'message' => 'Pengeluaran terbesar minggu ini jatuh pada ' . $dayName . ' sebesar Rp' . number_format($topDay->total, 0, ',', '.') . '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Rata-rata pengeluaran harian bulan ini
        |--------------------------------------------------------------------------
        */
        $daysInMonth = $now->day;
        if ($expenseThisMonth > 0 && $daysInMonth > 0) {
            $dailyAvg = round($expenseThisMonth / $daysInMonth);

            $insights[] = [
                'type' => 'daily_average',
                'icon' => 'bar_chart',
                'color' => '#3B82F6',
                'title' => 'Rata-rata Harian',
                'message' => 'Rata-rata pengeluaran harianmu bulan ini adalah Rp' . number_format($dailyAvg, 0, ',', '.') . '/hari.',
            ];
        }

        return response()->json([
            'month' => $currentMonth,
            'year' => $currentYear,
            'total_income' => (float) $incomeThisMonth,
            'total_expense' => (float) $expenseThisMonth,
            'insights' => $insights,
        ]);
    }
}
