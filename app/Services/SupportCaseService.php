<?php

namespace App\Services;

use App\Models\SupportCase;
use App\Models\SupportCaseMessage;
use App\Models\User;
use Illuminate\Support\Str;

class SupportCaseService
{
    public function __construct(protected AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): SupportCase
    {
        $case = SupportCase::query()->create([
            'case_number' => $this->nextCaseNumber((string) $data['type']),
            'type' => $data['type'],
            'status' => SupportCase::STATUS_SUBMITTED,
            'user_id' => $user->id,
            'transaction_id' => $data['transaction_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'evidence' => $data['evidence'] ?? null,
        ]);

        SupportCaseMessage::query()->create([
            'support_case_id' => $case->id,
            'user_id' => $user->id,
            'is_internal' => false,
            'body' => $data['description'],
        ]);

        return $case->fresh(['messages']);
    }

    public function acknowledge(SupportCase $case, ?User $owner = null): SupportCase
    {
        $from = $case->status;
        $case->update([
            'status' => SupportCase::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'owner_id' => $owner?->id ?? $case->owner_id,
        ]);

        $this->auditLogService->record(
            'support_case.acknowledge',
            $case,
            $owner,
            $from,
            SupportCase::STATUS_ACKNOWLEDGED,
        );

        return $case->fresh();
    }

    public function assign(SupportCase $case, User $owner): SupportCase
    {
        $from = $case->status;
        $case->update([
            'owner_id' => $owner->id,
            'status' => SupportCase::STATUS_ASSIGNED,
            'acknowledged_at' => $case->acknowledged_at ?? now(),
        ]);

        $this->auditLogService->record(
            'support_case.assign',
            $case,
            $owner,
            $from,
            SupportCase::STATUS_ASSIGNED,
        );

        return $case->fresh();
    }

    public function addMessage(SupportCase $case, User $actor, string $body, bool $internal = false): SupportCaseMessage
    {
        $message = SupportCaseMessage::query()->create([
            'support_case_id' => $case->id,
            'user_id' => $actor->id,
            'is_internal' => $internal,
            'body' => $body,
        ]);

        if (! $internal && $case->status === SupportCase::STATUS_AWAITING_CUSTOMER) {
            $case->update(['status' => SupportCase::STATUS_IN_PROGRESS]);
        } elseif (! $internal && in_array($case->status, [SupportCase::STATUS_ACKNOWLEDGED, SupportCase::STATUS_ASSIGNED], true)) {
            $case->update(['status' => SupportCase::STATUS_IN_PROGRESS]);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function decide(SupportCase $case, array $data, User $actor): SupportCase
    {
        $from = $case->status;
        $to = $data['status'] ?? SupportCase::STATUS_RESOLVED;

        $case->update([
            'decision' => $data['decision'] ?? $case->decision,
            'financial_outcome' => $data['financial_outcome'] ?? $case->financial_outcome,
            'admin_notes' => $data['admin_notes'] ?? $case->admin_notes,
            'status' => $to,
            'owner_id' => $case->owner_id ?? $actor->id,
            'closed_at' => in_array($to, [SupportCase::STATUS_RESOLVED, SupportCase::STATUS_CLOSED], true)
                ? now()
                : $case->closed_at,
        ]);

        if (! empty($data['message'])) {
            $this->addMessage($case, $actor, (string) $data['message'], false);
        }

        $this->auditLogService->record(
            'support_case.decide',
            $case,
            $actor,
            $from,
            $to,
            $data['decision'] ?? null,
            [
                'financial_outcome' => $data['financial_outcome'] ?? null,
            ],
        );

        return $case->fresh(['messages', 'owner']);
    }

    private function nextCaseNumber(string $type): string
    {
        $prefix = match ($type) {
            SupportCase::TYPE_DAMAGE => 'DMG',
            SupportCase::TYPE_DISPUTE => 'DSP',
            default => 'QRY',
        };

        return $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }
}
