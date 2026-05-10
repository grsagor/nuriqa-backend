<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('offered_unit_price', 10, 2);
            $table->string('status', 32)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_until')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'buyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_offers');
    }
};
