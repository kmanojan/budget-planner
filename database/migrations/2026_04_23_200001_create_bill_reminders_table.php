<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 10)->default('LKR');
            $table->date('due_date');
            $table->enum('frequency', ['once', 'monthly', 'yearly'])->default('monthly');
            $table->integer('remind_days_before')->default(3);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_paid', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_reminders');
    }
};
