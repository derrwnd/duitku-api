<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'recurring:process';

    protected $description = 'Process recurring transactions that are due today';

    public function handle(): int
    {
        $today = Carbon::today();

        $recurrings = RecurringTransaction::with([
            'wallet',
            'category'
        ])
        ->where('is_active', true)
        ->where('next_run_date', '<=', $today)
        ->get();

        $processed = 0;

        foreach ($recurrings as $recurring) {
            // Buat transaksi dari recurring
            $transaction = Transaction::create([
                'user_id' => $recurring->user_id,
                'wallet_id' => $recurring->wallet_id,
                'category_id' => $recurring->category_id,
                'recurring_id' => $recurring->id,
                'amount' => $recurring->amount,
                'type' => $recurring->category->type ?? 'expense',
                'note' => $recurring->note,
                'transaction_date' => $today,
            ]);

            // Update balance wallet
            $wallet = $recurring->wallet;
            if ($transaction->type === 'income') {
                $wallet->balance += $transaction->amount;
            } else {
                $wallet->balance -= $transaction->amount;
            }
            $wallet->save();

            // Hitung next_run_date berikutnya
            $nextDate = match ($recurring->frequency) {
                'daily' => Carbon::parse($recurring->next_run_date)->addDay(),
                'weekly' => Carbon::parse($recurring->next_run_date)->addWeek(),
                'monthly' => Carbon::parse($recurring->next_run_date)->addMonth(),
                'yearly' => Carbon::parse($recurring->next_run_date)->addYear(),
                default => Carbon::parse($recurring->next_run_date)->addMonth(),
            };

            $recurring->update([
                'next_run_date' => $nextDate,
            ]);

            // Kirim notifikasi ke user
            Notification::create([
                'user_id' => $recurring->user_id,
                'title' => 'Transaksi Otomatis',
                'message' => 'Transaksi berulang "' . ($recurring->note ?? $recurring->category->name) . '" sebesar Rp' . number_format($recurring->amount, 0, ',', '.') . ' telah dicatat.',
                'type' => 'recurring',
                'is_read' => false,
            ]);

            $processed++;
        }

        $this->info("Processed {$processed} recurring transactions.");

        return Command::SUCCESS;
    }
}
