<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('delivery_payer', 16)->nullable()->after('delivery_fee'); // buyer|donor|mixed
            $table->decimal('admin_fee_total', 10, 2)->nullable()->after('platform_fee_total');
            $table->decimal('processor_fee_total', 10, 2)->nullable()->after('admin_fee_total');
            $table->decimal('cause_allocation_total', 10, 2)->nullable()->after('donation_total');
            $table->string('refund_status', 32)->nullable()->after('status');
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('refund_status');
            $table->timestamp('cancelled_at')->nullable()->after('refunded_amount');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_payer',
                'admin_fee_total',
                'processor_fee_total',
                'cause_allocation_total',
                'refund_status',
                'refunded_amount',
                'cancelled_at',
                'completed_at',
            ]);
        });
    }
};
