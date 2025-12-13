<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'image')) {
                $table->string('image')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('users', 'signup_date')) {
                $table->timestamp('signup_date')->nullable()->after('image');
            }

            if (!Schema::hasColumn('users', 'lang_id')) {
                $table->unsignedBigInteger('lang_id')->nullable()->after('signup_date');
            }

            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('lang_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'image',
                'signup_date',
                'lang_id',
                'role_id',
            ]);
        });
    }
};
