<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('subscription_plan', ['free', 'pro'])->default('free')->after('device_token');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->string('stripe_customer_id')->nullable()->after('subscription_expires_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_expires_at', 'stripe_customer_id', 'stripe_subscription_id']);
        });
    }
};
