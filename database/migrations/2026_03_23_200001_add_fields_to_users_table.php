<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('password');
            $table->string('profile_image')->nullable()->after('google_id');
            $table->string('language', 5)->default('en')->after('profile_image');
            $table->string('theme', 10)->default('system')->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'profile_image', 'language', 'theme']);
        });
    }
};
