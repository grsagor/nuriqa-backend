<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\TraderDocument;
use App\Models\TraderVerification;
use App\Models\User;
use App\Services\TraderVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TraderVerificationAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trader;

    private TraderVerification $verification;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(TraderVerificationService::STORAGE_DISK);

        // The admin check expects Admin to be role id 1, as in production.
        Role::query()->delete();
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Individual', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Trader', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $adminRole = Role::query()->find(1);
        $traderRole = Role::query()->find(4);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->trader = User::factory()->create(['role_id' => $traderRole->id, 'name' => 'Amina Khan']);

        $category = Category::query()->create(['name' => 'Fabrics']);
        $this->verification = TraderVerification::forceCreate([
            'user_id' => $this->trader->id, 'status' => 'pending', 'submitted_at' => now(),
            'legal_name' => 'Fine Fabrics Ltd', 'business_type' => 'company', 'companies_house_number' => '12345678',
            'bank_account_holder' => 'Fine Fabrics Ltd', 'bank_sort_code' => '123456', 'bank_account_number' => '87654321',
            'product_category_ids' => [$category->id], 'website_url' => 'https://finefabrics.example.com',
            'confirm_products_genuine' => true, 'confirm_legal_compliance' => true, 'accepted_seller_terms' => true,
        ]);

        $path = 'trader-verification/'.$this->trader->id.'/abc.pdf';
        Storage::disk(TraderVerificationService::STORAGE_DISK)->put($path, '%PDF-1.4 fake');
        $this->verification->documents()->create(['type' => TraderDocument::IDENTITY, 'path' => $path, 'original_name' => 'passport.pdf', 'mime_type' => 'application/pdf', 'size' => 13]);
    }

    public function test_only_admins_can_reach_the_verification_screens(): void
    {
        $this->actingAs($this->trader)->get('/admin/trader-verifications')->assertRedirect();
        $this->actingAs($this->trader)->get('/admin/trader-verifications/documents/1')->assertRedirect();
        $this->actingAs($this->trader)->post('/admin/trader-verifications/'.$this->verification->id.'/approve')->assertRedirect();

        $this->assertSame('pending', $this->verification->fresh()->status);
        $this->actingAs($this->admin)->get('/admin/trader-verifications')->assertOk()->assertSee('Trader verifications');
    }

    public function test_list_defaults_to_submitted_applications_and_filters_by_status(): void
    {
        $other = User::factory()->create(['role_id' => 4]);
        TraderVerification::forceCreate(['user_id' => $other->id, 'status' => 'draft', 'legal_name' => 'Draft Co']);

        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $all = $this->actingAs($this->admin)->getJson('/admin/trader-verifications/list?status=all', $ajax)->assertOk()->json('data');
        $this->assertCount(1, $all);

        $drafts = $this->actingAs($this->admin)->getJson('/admin/trader-verifications/list?status=draft', $ajax)->json('data');
        $this->assertCount(1, $drafts);
        $this->assertStringContainsString('Draft Co', $drafts[0]['business']);
    }

    public function test_review_modal_shows_everything_the_admin_needs(): void
    {
        $html = $this->actingAs($this->admin)->getJson('/admin/trader-verifications/show/'.$this->verification->id)->assertOk()->json('html');

        foreach (['Amina Khan', 'Fine Fabrics Ltd', '12345678', '12-34-56', '87654321', 'Fabrics', 'passport.pdf', 'Approve trader', 'Reject application'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    public function test_decision_forms_only_appear_while_pending(): void
    {
        $this->verification->forceFill(['status' => 'approved'])->save();

        $html = $this->actingAs($this->admin)->getJson('/admin/trader-verifications/show/'.$this->verification->id)->json('html');
        $this->assertStringNotContainsString('Approve trader', $html);
    }

    public function test_admin_can_approve(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/trader-verifications/'.$this->verification->id.'/approve', ['internal_notes' => 'Checked CH'])
            ->assertOk()->assertJsonPath('success', true);

        $v = $this->verification->fresh();
        $this->assertSame('approved', $v->status);
        $this->assertSame($this->admin->id, $v->reviewed_by);
        $this->assertSame('Checked CH', $v->internal_notes);
        $this->assertTrue($this->trader->fresh()->canTrade());
    }

    public function test_rejection_needs_a_reason_the_trader_can_act_on(): void
    {
        $url = '/admin/trader-verifications/'.$this->verification->id.'/reject';

        $this->actingAs($this->admin)->postJson($url, ['rejection_reason' => 'no'])->assertStatus(422)->assertJsonValidationErrors('rejection_reason');
        $this->assertSame('pending', $this->verification->fresh()->status);

        $this->actingAs($this->admin)->postJson($url, ['rejection_reason' => 'The passport photo is unreadable, please upload it again.'])->assertOk();
        $v = $this->verification->fresh();
        $this->assertSame('rejected', $v->status);
        $this->assertSame('The passport photo is unreadable, please upload it again.', $v->rejection_reason);
        $this->assertFalse($this->trader->fresh()->canTrade());
    }

    public function test_an_already_decided_application_cannot_be_decided_again(): void
    {
        $this->verification->forceFill(['status' => 'approved'])->save();

        $this->actingAs($this->admin)->postJson('/admin/trader-verifications/'.$this->verification->id.'/reject', ['rejection_reason' => 'Changed our mind about this one.'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame('approved', $this->verification->fresh()->status);
    }

    public function test_document_download_is_private_and_recorded(): void
    {
        $doc = $this->verification->documents()->first();

        $response = $this->actingAs($this->admin)->get('/admin/trader-verifications/documents/'.$doc->id)->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'trader_verification.document_viewed', 'actor_id' => $this->admin->id, 'auditable_id' => $this->verification->id]);
    }
}
