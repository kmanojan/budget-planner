<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->decimal('exchange_rate', 10, 6)->nullable();
            $table->string('currency_code', 10);
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->time('transaction_time');
            $table->enum('status', ['completed', 'pending'])->default('completed');
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};