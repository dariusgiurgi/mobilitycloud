<?php

namespace App\Support;

use App\Models\PlatformSubscriptionEvent;
use App\Models\Workspace;

class PlatformSubscriptionTimeline
{
    public static function record(Workspace $workspace, string $eventType, string $summary, array $metadata = []): PlatformSubscriptionEvent
    {
        return $workspace->subscriptionEvents()->create([
            'actor_id' => auth()->id(),
            'event_type' => $eventType,
            'summary' => $summary,
            'metadata' => $metadata ?: null,
        ]);
    }
}
