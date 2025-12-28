<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // Drop foreign key constraint first
            if (Schema::hasColumn('products', 'condition_id')) {
                $table->dropForeign('products_condition_id_foreign');
                $table->dropColumn('condition_id');
            }

            // Add enum condition column
            if (!Schema::hasColumn('products', 'condition')) {
                $table->enum('condition', ['new', 'used'])->default('used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // Drop enum column
            if (Schema::hasColumn('products', 'condition')) {
                $table->dropColumn('condition');
            }

            // Restore condition_id column + foreign key
            if (!Schema::hasColumn('products', 'condition_id')) {
                $table->unsignedBigInteger('condition_id')->nullable();

                $table->foreign('condition_id')
                    ->references('id')
                    ->on('conditions')
                    ->onDelete('set null');
            }
        });
    }
};
