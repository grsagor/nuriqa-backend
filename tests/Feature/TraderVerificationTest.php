<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\TraderDocument;
use App\Models\TraderVerification;
use App\Models\User;
use App\Services\TraderVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class TraderVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $individualRole;

    private Role $traderRole;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(TraderVerificationService::STORAGE_DISK);
        $this->adminRole = Role::query()->create(['name' => 'Admin']);
        $this->individualRole = Role::query()->create(['name' => 'Individual']);
        $this->traderRole = Role::query()->firstOrCreate(['name' => 'Trader']);
    }

    private function as(User $user): self
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user));
    }

    private function trader(): User
    {
        return User::factory()->create(['role_id' => $this->traderRole->id]);
    }

    private function validFields(): array
    {
        $category = Category::query()->firstOrCreate(['name' => 'Cat']);

        return [
            'legal_name' => 'Fine Fabrics Ltd',
            'trading_name' => 'Fine Fabrics',
            'business_type' => 'company',
            'companies_house_number' => '12345678',
            'registered_address_line1' => '1 High Street',
            'registered_city' => 'Manchester',
            'registered_postcode' => 'm1 1aa',
            'registered_country' => 'GB',
            'trading_address_same_as_registered' => true,
            'returns_address_same_as_trading' => true,
            'owner_full_name' => 'Amina Khan',
            'owner_role' => 'director',
            'contact_name' => 'Amina Khan',
            'contact_email' => 'amina@example.com',
            'contact_phone' => '+44 7123 456789',
            'bank_account_holder' => 'Fine Fabrics Ltd',
            'bank_sort_code' => '12-34-56',
            'bank_account_number' => '12345678',
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'website_url' => 'https://finefabrics.example.com',
            'social_links' => ['https://instagram.com/finefabrics'],
            'product_category_ids' => [$category->id],
            'confirm_products_genuine' => true,
            'confirm_legal_compliance' => true,
            'accepted_seller_terms' => true,
        ];
    }

    private function upload(User $user, string $type, string $name = 'doc.pdf')
    {
        return $this->as($user)->post('/api/v1/trader/verification/documents', [
            'type' => $type,
            'file' => UploadedFile::fake()->create($name, 200, 'application/pdf'),
        ], ['Accept' => 'application/json']);
    }

    private function completeApplication(User $user): void
    {
        $this->as($user)->putJson('/api/v1/trader/verification', $this->validFields())->assertOk();
        foreach (TraderDocument::REQUIRED_TYPES as $type) {
            $this->upload($user, $type)->assertCreated();
        }
    }

    private function submitted(): array
    {
        $user = $this->trader();
        $this->completeApplication($user);
        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertOk();

        return [$user, $user->traderVerification()->first()];
    }

    // ---- signup -------------------------------------------------------------------------------

    public function test_signup_as_trader_creates_a_trader_account(): void
    {
        $this->postJson('/api/v1/signup', $this->signupPayload(['account_type' => 'trader']))->assertCreated()
            ->assertJsonPath('data.user.role', 'Trader')
            ->assertJsonPath('data.user.trader_verification_status', 'draft');
    }

    public function test_signup_cannot_choose_an_arbitrary_role_such_as_admin(): void
    {
        $this->postJson('/api/v1/signup', $this->signupPayload(['role_id' => $this->adminRole->id]))->assertCreated()
            ->assertJsonPath('data.user.role', 'Individual');

        $this->assertSame(0, User::query()->where('role_id', $this->adminRole->id)->count());
    }

    public function test_signup_rejects_an_unknown_account_type(): void
    {
        $this->postJson('/api/v1/signup', $this->signupPayload(['account_type' => 'admin']))->assertStatus(422);
    }

    private function signupPayload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Test Person', 'email' => 'person'.random_int(1, 99999).'@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'phone_country_code' => '+44', 'phone_number' => (string) random_int(7000000000, 7999999999),
        ], $extra);
    }

    // ---- the form -----------------------------------------------------------------------------

    public function test_only_traders_have_a_verification_application(): void
    {
        $individual = User::factory()->create(['role_id' => $this->individualRole->id]);

        $this->as($individual)->getJson('/api/v1/trader/verification')->assertForbidden();
        $this->as($this->trader())->getJson('/api/v1/trader/verification')->assertOk()->assertJsonPath('data.status', 'draft');
    }

    public function test_draft_saves_normalised_values_and_validates_formats(): void
    {
        $user = $this->trader();

        $this->as($user)->putJson('/api/v1/trader/verification', ['bank_sort_code' => '12345'])->assertStatus(422)->assertJsonValidationErrors('bank_sort_code');
        $this->as($user)->putJson('/api/v1/trader/verification', ['bank_account_number' => '1234567'])->assertStatus(422);
        $this->as($user)->putJson('/api/v1/trader/verification', ['companies_house_number' => 'bad'])->assertStatus(422);
        $this->as($user)->putJson('/api/v1/trader/verification', ['vat_number' => 'ABC'])->assertStatus(422);
        $this->as($user)->putJson('/api/v1/trader/verification', ['website_url' => 'javascript:alert(1)'])->assertStatus(422);
        $this->as($user)->putJson('/api/v1/trader/verification', ['product_category_ids' => [999999]])->assertStatus(422);

        // People type these in many formats; they are stored tidy.
        $this->as($user)->putJson('/api/v1/trader/verification', [
            'bank_sort_code' => '12-34-56', 'registered_postcode' => 'm1 1aa', 'vat_number' => 'gb 123 456 789', 'companies_house_number' => 'sc123456',
        ])->assertOk();

        $v = $user->traderVerification()->first();
        $this->assertSame('123456', $v->bank_sort_code);
        $this->assertSame('M1 1AA', $v->registered_postcode);
        $this->assertSame('GB123456789', $v->vat_number);
        $this->assertSame('SC123456', $v->companies_house_number);
    }

    public function test_bank_details_are_encrypted_at_rest_and_masked_in_responses(): void
    {
        $user = $this->trader();
        $this->as($user)->putJson('/api/v1/trader/verification', ['bank_sort_code' => '123456', 'bank_account_number' => '87654321'])->assertOk()
            ->assertJsonPath('data.bank_account_number_masked', '••••4321')
            ->assertJsonPath('data.bank_sort_code_masked', '••-••-56')
            ->assertJsonPath('data.fields.bank_account_number', null);

        $raw = DB::table('trader_verifications')->where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('87654321', (string) $raw->bank_account_number);
        $this->assertStringNotContainsString('123456', (string) $raw->bank_sort_code);
        $this->assertSame('87654321', $user->traderVerification()->first()->bank_account_number);
    }

    public function test_trader_cannot_set_status_or_review_fields_through_the_form(): void
    {
        $user = $this->trader();
        $this->as($user)->putJson('/api/v1/trader/verification', ['legal_name' => 'X Ltd', 'status' => 'approved', 'reviewed_by' => 1, 'internal_notes' => 'x'])->assertOk();

        $v = $user->traderVerification()->first();
        $this->assertSame('draft', $v->status);
        $this->assertNull($v->reviewed_by);
        $this->assertNull($v->internal_notes);
    }

    // ---- documents ----------------------------------------------------------------------------

    public function test_documents_are_stored_privately_and_only_safe_types_are_accepted(): void
    {
        $user = $this->trader();

        $this->upload($user, 'identity', 'passport.pdf')->assertCreated();
        $doc = TraderDocument::query()->firstOrFail();
        Storage::disk(TraderVerificationService::STORAGE_DISK)->assertExists($doc->path);
        $this->assertStringStartsWith('trader-verification/'.$user->id.'/', $doc->path);
        $this->assertStringNotContainsString('passport', $doc->path);

        $this->as($user)->post('/api/v1/trader/verification/documents', ['type' => 'identity', 'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->as($user)->post('/api/v1/trader/verification/documents', ['type' => 'selfie', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->as($user)->post('/api/v1/trader/verification/documents', ['type' => 'identity', 'file' => UploadedFile::fake()->create('big.pdf', 20000, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_documents_can_be_deleted_while_editable_and_are_limited_per_type(): void
    {
        $user = $this->trader();
        for ($i = 0; $i < 5; $i++) {
            $this->upload($user, 'product_evidence', "e{$i}.pdf")->assertCreated();
        }
        $this->upload($user, 'product_evidence', 'e6.pdf')->assertStatus(422);

        $doc = TraderDocument::query()->first();
        $this->as($user)->deleteJson('/api/v1/trader/verification/documents/'.$doc->id)->assertOk();
        Storage::disk(TraderVerificationService::STORAGE_DISK)->assertMissing($doc->path);
    }

    public function test_a_trader_cannot_delete_someone_elses_document(): void
    {
        $owner = $this->trader();
        $this->upload($owner, 'identity')->assertCreated();
        $doc = TraderDocument::query()->firstOrFail();

        $this->as($this->trader())->deleteJson('/api/v1/trader/verification/documents/'.$doc->id)->assertNotFound();
    }

    // ---- submit / review ----------------------------------------------------------------------

    public function test_incomplete_application_cannot_be_submitted(): void
    {
        $user = $this->trader();
        $this->as($user)->putJson('/api/v1/trader/verification', ['legal_name' => 'Only A Name'])->assertOk();

        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertStatus(422)
            ->assertJsonValidationErrors(['business_type', 'bank_account_number', 'accepted_seller_terms']);
        $this->assertSame('draft', $user->traderVerification()->first()->status);
    }

    public function test_documents_are_required_to_submit(): void
    {
        $user = $this->trader();
        $this->as($user)->putJson('/api/v1/trader/verification', $this->validFields())->assertOk();

        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertStatus(422)->assertJsonValidationErrors(['documents.identity', 'documents.bank_proof', 'documents.product_evidence']);
    }

    public function test_company_needs_a_companies_house_number_but_a_sole_trader_does_not(): void
    {
        $user = $this->trader();
        $this->completeApplication($user);
        $this->as($user)->putJson('/api/v1/trader/verification', ['companies_house_number' => null])->assertOk();
        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertStatus(422)->assertJsonValidationErrors('companies_house_number');

        $this->as($user)->putJson('/api/v1/trader/verification', ['business_type' => 'sole_trader'])->assertOk();
        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertOk();
    }

    public function test_submitting_locks_the_application_and_records_the_terms_acceptance(): void
    {
        [$user, $v] = $this->submitted();

        $this->assertSame('pending', $v->status);
        $this->assertNotNull($v->submitted_at);
        $this->assertSame(TraderVerificationService::TERMS_VERSION, $v->terms_version);
        $this->assertNotNull($v->terms_accepted_at);

        $this->as($user)->putJson('/api/v1/trader/verification', ['legal_name' => 'Changed'])->assertStatus(423);
        $this->upload($user, 'identity')->assertStatus(423);
        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertStatus(423);
    }

    public function test_approval_unlocks_trading_and_notifies_the_trader(): void
    {
        [$user, $v] = $this->submitted();
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);

        app(TraderVerificationService::class)->approve($v, $admin, 'Looks good');

        $this->assertTrue($user->fresh()->canTrade());
        $this->assertSame('approved', $user->fresh()->traderVerificationStatus());
        $this->assertDatabaseHas('seller_notifications', ['user_id' => $user->id, 'type' => 'trader_verification']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'trader_verification.approved', 'actor_id' => $admin->id]);
    }

    public function test_rejection_lets_the_trader_fix_and_resubmit(): void
    {
        [$user, $v] = $this->submitted();
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);

        app(TraderVerificationService::class)->reject($v, $admin, 'The bank statement is unreadable.');

        $this->as($user)->getJson('/api/v1/trader/verification')->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'The bank statement is unreadable.')->assertJsonPath('data.editable', true);
        $this->assertFalse($user->fresh()->canTrade());

        $this->upload($user, 'bank_proof', 'clearer.pdf')->assertCreated();
        $this->as($user)->postJson('/api/v1/trader/verification/submit')->assertOk();
        $this->assertSame('pending', $v->fresh()->status);
        $this->assertNull($v->fresh()->rejection_reason);
    }

    public function test_only_pending_applications_can_be_decided(): void
    {
        $user = $this->trader();
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $v = TraderVerification::forceCreate(['user_id' => $user->id, 'status' => 'draft']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TraderVerificationService::class)->approve($v, $admin);
    }

    // ---- buying and selling is blocked until approved -----------------------------------------

    public function test_unverified_traders_cannot_buy_or_sell(): void
    {
        $user = $this->trader();
        $blocked = [
            ['post', '/api/v1/cart', ['product_id' => 1]],
            ['post', '/api/v1/orders/checkout', []],
            ['post', '/api/v1/orders/create-payment-intent', []],
            ['post', '/api/v1/products/store', []],
            ['post', '/api/v1/product-price-offers', []],
            ['post', '/api/v1/withdrawals', []],
        ];

        foreach ($blocked as [$method, $url, $body]) {
            $this->as($user)->{$method.'Json'}($url, $body)->assertForbidden()
                ->assertJsonPath('code', 'trader_verification_required')
                ->assertJsonPath('verification_status', 'draft');
        }
    }

    public function test_pending_and_rejected_traders_are_still_blocked(): void
    {
        [$user, $v] = $this->submitted();
        $this->as($user)->postJson('/api/v1/cart', ['product_id' => 1])->assertForbidden()->assertJsonPath('verification_status', 'pending');

        $v->forceFill(['status' => 'rejected'])->save();
        $this->as($user)->postJson('/api/v1/cart', ['product_id' => 1])->assertForbidden()->assertJsonPath('verification_status', 'rejected');
    }

    public function test_approved_traders_and_individuals_are_not_blocked(): void
    {
        [$user, $v] = $this->submitted();
        app(TraderVerificationService::class)->approve($v, User::factory()->create(['role_id' => $this->adminRole->id]));
        $individual = User::factory()->create(['role_id' => $this->individualRole->id]);

        // Past the gate: the request now fails on its own validation (422), not on verification (403).
        $this->as($user)->postJson('/api/v1/products/store', [])->assertStatus(422);
        $this->as($individual)->postJson('/api/v1/products/store', [])->assertStatus(422);
    }

    public function test_unverified_traders_can_still_browse_message_and_manage_their_application(): void
    {
        $user = $this->trader();

        $this->as($user)->getJson('/api/v1/my-user-info')->assertOk()->assertJsonPath('data.trader_verification_status', 'draft');
        $this->as($user)->getJson('/api/v1/cart')->assertOk();
        $this->getJson('/api/v1/products')->assertOk();
    }
}
