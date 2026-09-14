<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsor_requests', function (Blueprint $table) {
            $table->text('moderation_message')->nullable()->after('status');
            $table->string('rejection_reason')->nullable()->after('moderation_message');
            $table->timestamp('moderated_at')->nullable()->after('rejection_reason');
            $table->foreignId('moderated_by')->nullable()->after('moderated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sponsor_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderation_message', 'rejection_reason', 'moderated_at']);
        });
    }
};
