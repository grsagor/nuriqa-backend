<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Buyer-chosen price per unit for flexible (charity-style) products; null = use the listing price.
            $table->decimal('contribution_amount', 10, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('contribution_amount');
        });
    }
};
