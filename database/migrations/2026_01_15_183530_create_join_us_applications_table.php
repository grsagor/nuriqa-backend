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
        Schema::create('join_us_applications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['model', 'volunteer']);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->integer('age')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality')->nullable();

            // Address fields
            $table->text('address')->nullable();
            $table->string('apartment_suite_unit')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();

            // Model-specific fields
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->json('comfort_preferences')->nullable();
            $table->json('model_experiences')->nullable();
            $table->text('model_motivation')->nullable();
            $table->json('model_images')->nullable();

            // Volunteer-specific fields
            $table->json('areas_of_interest')->nullable();
            $table->text('volunteer_experiences')->nullable();
            $table->json('availability')->nullable();
            $table->json('commitment_level')->nullable();
            $table->text('volunteer_motivation')->nullable();
            $table->string('cv_path')->nullable();

            // Agreements/Consents (stored as JSON)
            $table->json('agreements')->nullable();

            // Status
            $table->enum('status', ['pending', 'reviewed', 'accepted', 'rejected'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('join_us_applications');
    }
};
