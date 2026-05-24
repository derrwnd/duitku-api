<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use App\Models\Export;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ExportController extends Controller
{
    /**
     * Export laporan transaksi ke CSV
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense',
            'wallet_id' => 'nullable|exists:wallets,id',
            'format' => 'nullable|in:csv',
        ]);

        $userId = $request->user()->id;

        $query = Transaction::with(['wallet', 'category'])
            ->where('user_id', $userId);

        if (!empty($validated['start_date'])) {
            $query->where(
                'transaction_date',
                '>=',
                $validated['start_date']
            );
        }

        if (!empty($validated['end_date'])) {
            $query->where(
                'transaction_date',
                '<=',
                $validated['end_date']
            );
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['wallet_id'])) {
            $query->where('wallet_id', $validated['wallet_id']);
        }

        $transactions = $query
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Generate CSV content
        $csvHeader = [
            'Tanggal',
            'Tipe',
            'Kategori',
            'Dompet',
            'Nominal',
            'Catatan'
        ];

        $csvRows = $transactions->map(function ($t) {
            return [
                $t->transaction_date->format('Y-m-d'),
                $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                $t->category->name ?? '-',
                $t->wallet->name ?? '-',
                $t->amount,
                $t->note ?? '-',
            ];
        });

        // Build CSV string
        $csv = implode(',', $csvHeader) . "\n";
        foreach ($csvRows as $row) {
            $csv .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        // Simpan record export
        $filename = 'export_' . Carbon::now()->format('Ymd_His') . '.csv';

        Export::create([
            'user_id' => $userId,
            'format' => 'csv',
            'file_path' => $filename,
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
