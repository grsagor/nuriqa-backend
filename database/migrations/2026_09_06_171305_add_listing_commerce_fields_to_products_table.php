<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('guide_value', 10, 2)->nullable()->after('price');
            $table->string('contribution_mode', 16)->default('fixed')->after('is_free'); // fixed|flexible
            $table->string('delivery_payer', 16)->default('buyer')->after('contribution_mode'); // buyer|donor
            $table->string('cause_allocation_type', 32)->nullable()->after('donation_percentage'); // none|fixed|percentage|origin_profit
            $table->decimal('cause_allocation_value', 10, 2)->nullable()->after('cause_allocation_type');
            $table->foreignId('cause_id')->nullable()->after('cause_allocation_value')->constrained('causes')->nullOnDelete();
            $table->string('rejection_reason')->nullable()->after('approval_status');
            $table->text('moderation_message')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cause_id');
            $table->dropColumn([
                'guide_value',
                'contribution_mode',
                'delivery_payer',
                'cause_allocation_type',
                'cause_allocation_value',
                'rejection_reason',
                'moderation_message',
            ]);
        });
    }
};
