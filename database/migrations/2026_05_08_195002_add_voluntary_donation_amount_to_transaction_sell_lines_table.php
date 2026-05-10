<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->decimal('voluntary_donation_amount', 10, 2)->default(0)->after('donation_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('voluntary_donation_amount');
        });
    }
};
