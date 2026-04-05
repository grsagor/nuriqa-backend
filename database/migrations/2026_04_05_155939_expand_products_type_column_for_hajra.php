<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow product type values such as merchandise, seller, and hajra.
     * MySQL may have ENUM or a short VARCHAR from older schemas; this normalizes to VARCHAR(50).
     */
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `products` MODIFY `type` VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
