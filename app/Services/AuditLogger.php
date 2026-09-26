<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Journal d'audit des actions administratives et sensibles. */
class AuditLogger
{
    public static function log(string $action, ?Model $subject = null, array $data = []): void
    {
        $request = request();
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'data' => $data ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
