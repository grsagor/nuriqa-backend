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
        Schema::table('seller_payment_methods', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('provider');
            $table->string('status')->default('active')->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_payment_methods', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'status']);
        });
    }
};
