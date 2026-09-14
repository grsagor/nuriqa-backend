<?php

namespace App\Services;

use App\Models\SponsorRequest;
use App\Models\User;

class SponsorRequestModerationService
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function approve(SponsorRequest $request, ?User $actor = null, ?string $message = null): SponsorRequest
    {
        return $this->transition($request, 'approved', 'sponsor_request.approve', $actor, $message);
    }

    public function reject(SponsorRequest $request, ?User $actor = null, ?string $message = null, ?string $rejectionReason = null): SponsorRequest
    {
        return $this->transition($request, 'rejected', 'sponsor_request.reject', $actor, $message, $rejectionReason);
    }

    public function returnForCorrection(SponsorRequest $request, User $actor, string $message): SponsorRequest
    {
        return $this->transition($request, 'returned', 'sponsor_request.return_for_correction', $actor, $message);
    }

    public function resubmit(SponsorRequest $request, ?User $actor = null): SponsorRequest
    {
        return $this->transition($request, 'pending', 'sponsor_request.resubmit', $actor, null);
    }

    private function transition(
        SponsorRequest $request,
        string $toStatus,
        string $action,
        ?User $actor = null,
        ?string $message = null,
        ?string $rejectionReason = null,
    ): SponsorRequest {
        $from = $request->status;

        $request->update([
            'status' => $toStatus,
            'moderation_message' => $message,
            'rejection_reason' => $rejectionReason ?? ($toStatus === 'rejected' ? $message : $request->rejection_reason),
            'moderated_at' => now(),
            'moderated_by' => $actor?->id,
        ]);

        $this->auditLogService->record(
            $action,
            $request,
            $actor,
            $from,
            $toStatus,
            $message,
        );

        return $request->fresh();
    }
}
