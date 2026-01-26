<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rating')) {
                $table->decimal('rating', 3, 2)->nullable()->after('image');
            }
            if (!Schema::hasColumn('users', 'reviews')) {
                $table->integer('reviews')->nullable()->after('rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'reviews']);
        });
    }
};