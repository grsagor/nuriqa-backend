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
        Schema::dropIfExists('seller_notification_reads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('seller_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('entity_id');
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'type', 'entity_id']);
        });
    }
};
