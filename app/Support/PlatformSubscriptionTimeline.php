<?php

namespace App\Support;

use App\Models\PlatformSubscriptionEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;

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

    public static function recordAccount(User $account, string $eventType, string $summary, array $metadata = []): PlatformSubscriptionEvent
    {
        $workspace = app(AccountWorkspaceService::class)->ensureFor($account);

        return PlatformSubscriptionEvent::create([
            'workspace_id' => $workspace->id,
            'user_id' => $account->id,
            'actor_id' => auth()->id(),
            'event_type' => $eventType,
            'summary' => $summary,
            'metadata' => $metadata ?: null,
        ]);
    }
}
