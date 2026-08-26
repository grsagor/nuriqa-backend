<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shipments', 'seller_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->foreignId('seller_id')->nullable()->after('transaction_id')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('shipments', 'shipping_fee')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->decimal('shipping_fee', 10, 2)->nullable()->after('status');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shipments MODIFY tracking_number VARCHAR(255) NULL');
            DB::statement("ALTER TABLE shipments MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipments', 'seller_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('seller_id');
            });
        }

        if (Schema::hasColumn('shipments', 'shipping_fee')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('shipping_fee');
            });
        }
    }
};
