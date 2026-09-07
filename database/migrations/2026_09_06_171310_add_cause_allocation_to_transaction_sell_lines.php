<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->foreignId('cause_id')->nullable()->after('voluntary_donation_amount')->constrained('causes')->nullOnDelete();
            $table->decimal('cause_allocation_amount', 10, 2)->nullable()->after('cause_id');
            $table->decimal('contribution_amount', 10, 2)->nullable()->after('cause_allocation_amount');
            $table->string('delivery_payer', 16)->nullable()->after('contribution_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cause_id');
            $table->dropColumn(['cause_allocation_amount', 'contribution_amount', 'delivery_payer']);
        });
    }
};
