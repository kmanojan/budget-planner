<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('savings_goal_transactions')) {
            Schema::create('savings_goal_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('savings_goal_id')->constrained('savings_goals')->cascadeOnDelete();
                $table->enum('type', ['deposit', 'withdraw']);
                $table->decimal('amount', 15, 2);
                $table->string('note')->nullable();
                $table->timestamp('transaction_date')->useCurrent();
                $table->decimal('balance_after', 15, 2);
                $table->timestamps();

                $table->index(['savings_goal_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_goal_transactions');
    }
};
