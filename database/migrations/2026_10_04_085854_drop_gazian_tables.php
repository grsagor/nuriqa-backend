<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('gazian_trade_enquiries');
        Schema::dropIfExists('gazian_newsletter_subscribers');
    }

    public function down(): void
    {
        Schema::create('gazian_newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('gazian_trade_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('interest');
            $table->string('business_type');
            $table->json('explore');
            $table->string('country');
            $table->string('city');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }
};
