<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        string $action,
        Model $auditable,
        ?User $actor = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $message = null,
        array $meta = [],
    ): AuditLog {
        $actor ??= auth()->user();

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'message' => $message,
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => request()?->ip(),
        ]);
    }
}
