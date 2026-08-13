<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_groups') && !Schema::hasColumn('event_groups', 'type')) {
            Schema::table('event_groups', function (Blueprint $table) {
                $table->enum('type', ['budget', 'notes', 'todo'])->default('budget')->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('event_groups') && Schema::hasColumn('event_groups', 'type')) {
            Schema::table('event_groups', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
