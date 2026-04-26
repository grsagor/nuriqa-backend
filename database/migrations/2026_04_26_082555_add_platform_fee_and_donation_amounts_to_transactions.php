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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('platform_fee_total', 10, 2)->default(0)->after('subtotal');
            $table->decimal('donation_total', 10, 2)->default(0)->after('platform_fee_total');
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->decimal('platform_fee_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('donation_amount', 10, 2)->nullable()->after('platform_fee_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_amount', 'donation_amount']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_total', 'donation_total']);
        });
    }
};
