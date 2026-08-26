<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carrier', 50)->default('evri');
            $table->string('tracking_number')->nullable();
            $table->string('label_url')->nullable();
            $table->string('status', 32)->default('pending');
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->json('address_to')->nullable();
            $table->json('address_from')->nullable();
            $table->integer('weight_g')->nullable();
            $table->json('dimensions_cm')->nullable();
            $table->timestamps();

            $table->index(['carrier', 'tracking_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
