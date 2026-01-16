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
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->foreignId('sponsor_request_id')->nullable()->after('product_id')->constrained('sponsor_requests')->onDelete('set null');
            $table->foreignId('requester_user_id')->nullable()->after('sponsor_request_id')->constrained('users')->onDelete('set null');
            $table->foreignId('sponsor_user_id')->nullable()->after('requester_user_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropForeign(['sponsor_request_id']);
            $table->dropForeign(['requester_user_id']);
            $table->dropForeign(['sponsor_user_id']);
            $table->dropColumn(['sponsor_request_id', 'requester_user_id', 'sponsor_user_id']);
        });
    }
};
