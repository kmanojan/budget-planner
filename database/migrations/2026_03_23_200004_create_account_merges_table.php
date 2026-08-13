<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('merge_group_id');
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['merge_group_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_merges');
    }
};
