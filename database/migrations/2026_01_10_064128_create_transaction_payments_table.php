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
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->enum('payment_method', ['stripe', 'paypal', 'bank', 'cod'])->default('stripe');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
