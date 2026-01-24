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
            $table->string('carrier', 50)->default('evri');
            $table->string('tracking_number');
            $table->string('label_url')->nullable(); // S3 URL
            $table->enum('status', ['created', 'in_transit', 'delivered', 'failed', 'cancelled'])->default('created');
            $table->json('address_to')->nullable(); // Delivery address
            $table->json('address_from')->nullable(); // Origin address
            $table->integer('weight_g')->nullable();
            $table->json('dimensions_cm')->nullable(); // {length, width, height}
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
