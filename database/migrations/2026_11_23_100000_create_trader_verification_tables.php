<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Traders are a separate role; accounts start as Trader but cannot buy or sell until approved.
        if (! DB::table('roles')->whereRaw('LOWER(name) = ?', ['trader'])->exists()) {
            DB::table('roles')->insert(['name' => 'Trader', 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::create('trader_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('draft')->index(); // draft|pending|approved|rejected

            // Business
            $table->string('legal_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('business_type', 20)->nullable(); // company|partnership|sole_trader
            $table->string('companies_house_number', 16)->nullable();

            // Addresses
            $table->string('registered_address_line1')->nullable();
            $table->string('registered_address_line2')->nullable();
            $table->string('registered_city')->nullable();
            $table->string('registered_postcode', 16)->nullable();
            $table->string('registered_country', 2)->default('GB');
            $table->boolean('trading_address_same_as_registered')->default(true);
            $table->string('trading_address_line1')->nullable();
            $table->string('trading_address_line2')->nullable();
            $table->string('trading_city')->nullable();
            $table->string('trading_postcode', 16)->nullable();
            $table->string('trading_country', 2)->nullable();
            $table->boolean('returns_address_same_as_trading')->default(true);
            $table->string('returns_address_line1')->nullable();
            $table->string('returns_address_line2')->nullable();
            $table->string('returns_city')->nullable();
            $table->string('returns_postcode', 16)->nullable();
            $table->string('returns_country', 2)->nullable();

            // Owner / director and contact
            $table->string('owner_full_name')->nullable();
            $table->string('owner_role', 20)->nullable(); // director|owner|partner|other
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();

            // Bank account in the trader's name (number and sort code are stored encrypted)
            $table->string('bank_account_holder')->nullable();
            $table->text('bank_sort_code')->nullable();
            $table->text('bank_account_number')->nullable();

            // VAT, online presence, products
            $table->boolean('vat_registered')->nullable();
            $table->string('vat_number', 20)->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();
            $table->json('product_category_ids')->nullable();

            // Declarations
            $table->boolean('confirm_products_genuine')->default(false);
            $table->boolean('confirm_legal_compliance')->default(false);
            $table->boolean('accepted_seller_terms')->default(false);
            $table->string('terms_version', 32)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_accepted_ip', 45)->nullable();

            // Review
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('trader_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trader_verification_id')->constrained('trader_verifications')->cascadeOnDelete();
            $table->string('type', 32); // identity|bank_proof|product_evidence|business_proof
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index(['trader_verification_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_documents');
        Schema::dropIfExists('trader_verifications');
        DB::table('roles')->whereRaw('LOWER(name) = ?', ['trader'])->delete();
    }
};
