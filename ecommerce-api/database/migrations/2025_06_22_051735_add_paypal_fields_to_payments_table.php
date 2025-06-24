<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
            // Add PayPal fields to the payments table
            $table->string('paypal_order_id')->nullable()->after('payment_intent_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //drop the PayPal fields from the payments table
            $table->dropColumn(['paypal_order_id', 'paypal_capture_id']);
        });
    }
};
