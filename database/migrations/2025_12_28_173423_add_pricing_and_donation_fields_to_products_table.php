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
        Schema::table('products', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_free')->default(false);
                $table->boolean('discount_enabled')->default(false);
                $table->enum('discount_type', ['percentage', 'flat'])->nullable();
                $table->decimal('discount', 10, 2)->default(0);

                $table->boolean('platform_donation')->default(false);
                $table->unsignedTinyInteger('donation_percentage')->default(0);

                $table->boolean('active_listing')->default(true);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn([
                    'is_free',
                    'discount_enabled',
                    'discount_type',
                    'discount',
                    'platform_donation',
                    'donation_percentage',
                    'active_listing',
                ]);
            });
        });
    }
};
