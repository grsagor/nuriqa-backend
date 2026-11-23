<?php

namespace App\Services;

use App\Models\SellerNotification;
use App\Models\TraderDocument;
use App\Models\TraderVerification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TraderVerificationService
{
    /** Bump when the seller terms change, so each acceptance records which version was agreed. */
    public const TERMS_VERSION = '2026-11';

    public const STORAGE_DISK = 'local'; // private: files are never served from a public URL

    public function __construct(protected AuditLogService $auditLogService) {}

    /** Format rules, used when saving a draft (every field optional, but whatever is given must be valid). */
    public static function draftRules(): array
    {
        return [
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', Rule::in(['company', 'partnership', 'sole_trader'])],
            'companies_house_number' => ['nullable', 'string', 'regex:/^(?:[A-Z]{2}\d{6}|\d{8})$/'],

            'registered_address_line1' => ['nullable', 'string', 'max:255'],
            'registered_address_line2' => ['nullable', 'string', 'max:255'],
            'registered_city' => ['nullable', 'string', 'max:120'],
            'registered_postcode' => ['nullable', 'string', 'max:16'],
            'registered_country' => ['nullable', 'string', 'size:2'],
            'trading_address_same_as_registered' => ['nullable', 'boolean'],
            'trading_address_line1' => ['nullable', 'string', 'max:255'],
            'trading_address_line2' => ['nullable', 'string', 'max:255'],
            'trading_city' => ['nullable', 'string', 'max:120'],
            'trading_postcode' => ['nullable', 'string', 'max:16'],
            'trading_country' => ['nullable', 'string', 'size:2'],
            'returns_address_same_as_trading' => ['nullable', 'boolean'],
            'returns_address_line1' => ['nullable', 'string', 'max:255'],
            'returns_address_line2' => ['nullable', 'string', 'max:255'],
            'returns_city' => ['nullable', 'string', 'max:120'],
            'returns_postcode' => ['nullable', 'string', 'max:16'],
            'returns_country' => ['nullable', 'string', 'size:2'],

            'owner_full_name' => ['nullable', 'string', 'max:255'],
            'owner_role' => ['nullable', Rule::in(['director', 'owner', 'partner', 'other'])],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],

            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_sort_code' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            'bank_account_number' => ['nullable', 'string', 'regex:/^\d{8}$/'],

            'vat_registered' => ['nullable', 'boolean'],
            'vat_number' => ['nullable', 'string', 'regex:/^(?:GB)?(?:\d{9}|\d{12}|GD\d{3}|HA\d{3})$/'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'social_links' => ['nullable', 'array', 'max:6'],
            'social_links.*' => ['url:http,https', 'max:255'],
            'product_category_ids' => ['nullable', 'array', 'max:50'],
            'product_category_ids.*' => ['integer', 'exists:categories,id'],

            'confirm_products_genuine' => ['nullable', 'boolean'],
            'confirm_legal_compliance' => ['nullable', 'boolean'],
            'accepted_seller_terms' => ['nullable', 'boolean'],
        ];
    }

    /** Plain-English field names for validation messages ("registered address", not "registered address line1"). */
    public static function attributeNames(): array
    {
        return [
            'legal_name' => 'legal name', 'trading_name' => 'trading name', 'business_type' => 'business type',
            'companies_house_number' => 'Companies House number',
            'registered_address_line1' => 'registered address (line 1)', 'registered_address_line2' => 'registered address (line 2)',
            'registered_city' => 'registered town or city', 'registered_postcode' => 'registered postcode', 'registered_country' => 'registered country',
            'trading_address_line1' => 'trading address (line 1)', 'trading_address_line2' => 'trading address (line 2)',
            'trading_city' => 'trading town or city', 'trading_postcode' => 'trading postcode', 'trading_country' => 'trading country',
            'returns_address_line1' => 'returns address (line 1)', 'returns_address_line2' => 'returns address (line 2)',
            'returns_city' => 'returns town or city', 'returns_postcode' => 'returns postcode', 'returns_country' => 'returns country',
            'owner_full_name' => "owner or director's name", 'owner_role' => "owner or director's role",
            'contact_name' => 'contact name', 'contact_email' => 'contact email', 'contact_phone' => 'contact phone number',
            'bank_account_holder' => 'account holder name', 'bank_sort_code' => 'sort code (6 digits)', 'bank_account_number' => 'account number (8 digits)',
            'vat_registered' => 'VAT registration answer', 'vat_number' => 'VAT number', 'website_url' => 'website', 'social_links' => 'social media links',
            'product_category_ids' => 'product categories',
            'confirm_products_genuine' => 'confirmation that your products are genuine',
            'confirm_legal_compliance' => 'confirmation of safety and legal compliance',
            'accepted_seller_terms' => "agreement to Origin's seller terms",
        ];
    }

    /** Everything that must be present and true before an application can be submitted. */
    public static function submitRules(array $data): array
    {
        $required = ['required'];
        $rules = self::draftRules();

        foreach ([
            'legal_name', 'business_type', 'registered_address_line1', 'registered_city', 'registered_postcode', 'registered_country',
            'owner_full_name', 'owner_role', 'contact_name', 'contact_email', 'contact_phone',
            'bank_account_holder', 'bank_sort_code', 'bank_account_number',
        ] as $field) {
            $rules[$field] = array_merge($required, array_diff($rules[$field], ['nullable']));
        }

        $rules['vat_registered'] = ['required', 'boolean'];
        $rules['product_category_ids'] = ['required', 'array', 'min:1', 'max:50'];
        $rules['companies_house_number'] = [Rule::requiredIf(($data['business_type'] ?? null) === 'company'), 'nullable', 'string', 'regex:/^(?:[A-Z]{2}\d{6}|\d{8})$/'];
        $rules['vat_number'] = [Rule::requiredIf(! empty($data['vat_registered'])), 'nullable', 'string', 'regex:/^(?:GB)?(?:\d{9}|\d{12}|GD\d{3}|HA\d{3})$/'];

        if (empty($data['trading_address_same_as_registered'])) {
            foreach (['trading_address_line1', 'trading_city', 'trading_postcode', 'trading_country'] as $field) {
                $rules[$field] = array_merge(['required'], array_diff($rules[$field], ['nullable']));
            }
        }
        if (empty($data['returns_address_same_as_trading'])) {
            foreach (['returns_address_line1', 'returns_city', 'returns_postcode', 'returns_country'] as $field) {
                $rules[$field] = array_merge(['required'], array_diff($rules[$field], ['nullable']));
            }
        }

        foreach (['confirm_products_genuine', 'confirm_legal_compliance', 'accepted_seller_terms'] as $checkbox) {
            $rules[$checkbox] = ['accepted'];
        }

        return $rules;
    }

    /** Tidy input into the stored form: digits only for bank details, upper case for registry numbers. */
    public static function normalize(array $data): array
    {
        foreach (['bank_sort_code', 'bank_account_number'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = preg_replace('/\D/', '', (string) $data[$field]);
            }
        }
        foreach (['companies_house_number', 'vat_number'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = strtoupper(preg_replace('/\s+/', '', (string) $data[$field]));
            }
        }
        foreach (['registered_postcode', 'trading_postcode', 'returns_postcode'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = strtoupper(trim((string) $data[$field]));
            }
        }
        foreach (['registered_country', 'trading_country', 'returns_country'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = strtoupper((string) $data[$field]);
            }
        }

        return $data;
    }

    /** Fields as stored, for validating the saved application as a whole. */
    public function currentData(TraderVerification $verification): array
    {
        // only() reads the decrypted attribute values; the model's $hidden list only affects JSON output.
        return $verification->only(TraderVerification::FILLABLE_BY_TRADER);
    }

    /**
     * Human-readable list of what is still missing (fields and documents), for the form's checklist.
     *
     * @return list<string>
     */
    public function missingRequirements(TraderVerification $verification): array
    {
        $data = $this->currentData($verification);
        $missing = array_keys(Validator::make($data, self::submitRules($data))->errors()->messages());

        $present = $verification->documents()->pluck('type')->unique()->all();
        foreach (TraderDocument::REQUIRED_TYPES as $type) {
            if (! in_array($type, $present, true)) {
                $missing[] = 'document_'.$type;
            }
        }

        return array_values(array_unique($missing));
    }

    /** @throws ValidationException when the application is incomplete */
    public function submit(TraderVerification $verification, User $actor): void
    {
        $data = $this->currentData($verification);
        Validator::make($data, self::submitRules($data), [], self::attributeNames())->validate();

        $missingDocs = array_diff(
            TraderDocument::REQUIRED_TYPES,
            $verification->documents()->pluck('type')->unique()->all()
        );
        if ($missingDocs !== []) {
            throw ValidationException::withMessages(
                collect($missingDocs)->mapWithKeys(fn ($t) => ['documents.'.$t => 'Please upload at least one '.str_replace('_', ' ', $t).' document.'])->all()
            );
        }

        $from = $verification->status;
        $verification->forceFill([
            'status' => TraderVerification::PENDING,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
            'terms_version' => self::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'terms_accepted_ip' => request()?->ip(),
        ])->save();

        $this->auditLogService->record('trader_verification.submitted', $verification, $actor, $from, TraderVerification::PENDING);
    }

    public function approve(TraderVerification $verification, User $admin, ?string $notes = null): void
    {
        $this->decide($verification, $admin, TraderVerification::APPROVED, null, $notes);
        $this->notify($verification, 'Your trader account is verified', 'You can now buy and sell on Nuriqa.');
    }

    public function reject(TraderVerification $verification, User $admin, string $reason, ?string $notes = null): void
    {
        $this->decide($verification, $admin, TraderVerification::REJECTED, $reason, $notes);
        $this->notify($verification, 'Your trader verification needs changes', $reason);
    }

    protected function decide(TraderVerification $verification, User $admin, string $to, ?string $reason, ?string $notes): void
    {
        if ($verification->status !== TraderVerification::PENDING) {
            throw ValidationException::withMessages(['status' => 'Only applications that are waiting for review can be decided.']);
        }

        $from = $verification->status;
        $verification->forceFill([
            'status' => $to,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
            'internal_notes' => $notes ?? $verification->internal_notes,
        ])->save();

        $this->auditLogService->record('trader_verification.'.$to, $verification, $admin, $from, $to, $reason);
    }

    protected function notify(TraderVerification $verification, string $title, string $description): void
    {
        SellerNotification::query()->create([
            'user_id' => $verification->user_id,
            'type' => 'trader_verification',
            'entity_type' => 'trader_verification',
            'entity_id' => $verification->id,
            'title' => $title,
            'description' => $description,
            'read' => 0,
        ]);
    }

    public function deleteDocumentFile(TraderDocument $document): void
    {
        Storage::disk(self::STORAGE_DISK)->delete($document->path);
    }

    /** Shape sent to the trader's own form; bank details are masked and file paths never leave the server. */
    public function present(TraderVerification $verification): array
    {
        $data = $this->currentData($verification);
        $sort = (string) ($data['bank_sort_code'] ?? '');
        $account = (string) ($data['bank_account_number'] ?? '');

        $data['bank_sort_code'] = null;
        $data['bank_account_number'] = null;

        return [
            'status' => $verification->status,
            'editable' => $verification->isEditable(),
            'rejection_reason' => $verification->status === TraderVerification::REJECTED ? $verification->rejection_reason : null,
            'submitted_at' => $verification->submitted_at?->toIso8601String(),
            'reviewed_at' => $verification->reviewed_at?->toIso8601String(),
            'fields' => $data,
            'bank_details_saved' => $sort !== '' && $account !== '',
            'bank_sort_code_masked' => $sort !== '' ? '••-••-'.substr($sort, -2) : null,
            'bank_account_number_masked' => $account !== '' ? '••••'.substr($account, -4) : null,
            'documents' => $verification->documents()->orderBy('id')->get()->map(fn (TraderDocument $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'original_name' => $d->original_name,
                'mime_type' => $d->mime_type,
                'size' => $d->size,
                'created_at' => $d->created_at?->toIso8601String(),
            ])->all(),
            'required_documents' => TraderDocument::REQUIRED_TYPES,
            'missing' => $verification->isEditable() ? $this->missingRequirements($verification) : [],
            'terms_version' => self::TERMS_VERSION,
        ];
    }
}
