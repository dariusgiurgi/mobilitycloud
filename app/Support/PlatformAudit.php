<?php

namespace App\Support;

use App\Models\PlatformAuditLog;
use Illuminate\Database\Eloquent\Model;

class PlatformAudit
{
    public static function log(string $action, string $description, ?Model $subject = null, array $metadata = []): void
    {
        PlatformAuditLog::create([
            'actor_id' => auth()->id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
