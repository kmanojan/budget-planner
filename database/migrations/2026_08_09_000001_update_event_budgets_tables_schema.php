<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add budget_id to events table if missing
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'budget_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreignId('budget_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            });
        }

        // 2. Add direct attribute columns to event_attributes if missing
        if (Schema::hasTable('event_attributes')) {
            Schema::table('event_attributes', function (Blueprint $table) {
                if (!Schema::hasColumn('event_attributes', 'type')) {
                    $table->enum('type', ['budget', 'notes', 'todo'])->default('budget')->after('event_group_id');
                }
                if (!Schema::hasColumn('event_attributes', 'name')) {
                    $table->string('name')->default('')->after('type');
                }
                if (!Schema::hasColumn('event_attributes', 'expected_amount')) {
                    $table->decimal('expected_amount', 15, 2)->nullable()->after('name');
                }
                if (!Schema::hasColumn('event_attributes', 'actual_amount')) {
                    $table->decimal('actual_amount', 15, 2)->nullable()->after('expected_amount');
                }
                if (!Schema::hasColumn('event_attributes', 'content')) {
                    $table->text('content')->nullable()->after('actual_amount');
                }
                if (!Schema::hasColumn('event_attributes', 'is_done')) {
                    $table->boolean('is_done')->default(false)->after('content');
                }
                if (!Schema::hasColumn('event_attributes', 'due_date')) {
                    $table->date('due_date')->nullable()->after('is_done');
                }
            });
        }

        // 3. Drop old sub-item tables if present
        Schema::dropIfExists('event_todo_items');
        Schema::dropIfExists('event_notes');
        Schema::dropIfExists('event_budget_items');
    }

    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'budget_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['budget_id']);
                $table->dropColumn('budget_id');
            });
        }
    }
};
