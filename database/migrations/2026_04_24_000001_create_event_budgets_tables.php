<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. events
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('month_id', 20)->nullable(); // e.g. "2026-09"
            $table->string('title');
            $table->date('event_date')->nullable();
            $table->decimal('total_expected_budget', 15, 2)->default(0);
            $table->decimal('total_actual_budget', 15, 2)->default(0);
            $table->enum('status', ['planning', 'ongoing', 'completed'])->default('planning');
            $table->timestamps();

            $table->index(['user_id', 'budget_id']);
            $table->index(['user_id', 'month_id']);
        });

        // 2. event_groups (tabs)
        Schema::create('event_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });

        // 3. event_attributes (direct attributes inside group: budget/notes/todo)
        Schema::create('event_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_group_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['budget', 'notes', 'todo']);
            $table->string('name');
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->decimal('actual_amount', 15, 2)->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_done')->default(false);
            $table->date('due_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attributes');
        Schema::dropIfExists('event_groups');
        Schema::dropIfExists('events');
    }
};
