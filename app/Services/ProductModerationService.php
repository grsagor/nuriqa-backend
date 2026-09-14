<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductApprovalHistory;
use App\Models\User;

class ProductModerationService
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function record(
        Product $product,
        string $toStatus,
        string $action,
        ?User $actor = null,
        ?string $message = null,
        array $meta = [],
    ): ProductApprovalHistory {
        return ProductApprovalHistory::query()->create([
            'product_id' => $product->id,
            'actor_id' => $actor?->id,
            'from_status' => $product->approval_status,
            'to_status' => $toStatus,
            'action' => $action,
            'message' => $message,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function transition(
        Product $product,
        string $toStatus,
        string $action,
        ?User $actor = null,
        ?string $message = null,
        ?string $rejectionReason = null,
    ): Product {
        $from = $product->approval_status;
        $this->record($product, $toStatus, $action, $actor, $message);

        $product->update([
            'approval_status' => $toStatus,
            'rejection_reason' => $rejectionReason,
            'moderation_message' => $message,
            'active_listing' => $toStatus === Product::APPROVAL_APPROVED ? $product->active_listing : false,
        ]);

        $this->auditLogService->record(
            'product.'.$action,
            $product,
            $actor,
            $from,
            $toStatus,
            $message,
            $rejectionReason ? ['rejection_reason' => $rejectionReason] : [],
        );

        return $product->fresh();
    }
}
