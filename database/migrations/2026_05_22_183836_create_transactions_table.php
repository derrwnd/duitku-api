<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('wallet_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('recurring_id')
                ->nullable()
                ->constrained('recurring_transactions')
                ->nullOnDelete();

            $table->decimal('amount', 15, 2);

            $table->enum('type', [
                'income',
                'expense'
            ]);

            $table->text('note')->nullable();

            $table->date('transaction_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};