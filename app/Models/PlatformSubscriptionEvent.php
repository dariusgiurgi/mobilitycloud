<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSubscriptionEvent extends Model
{
    protected $fillable = [
        'workspace_id', 'actor_id', 'event_type', 'summary', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function typeOptions(): array
    {
        return [
            'manual_note' => 'Manual note',
            'plan_changed' => 'Plan changed',
            'status_changed' => 'Status changed',
            'trial_extended' => 'Trial extended',
            'trial_updated' => 'Trial updated',
            'activated' => 'Activated',
            'billing_updated' => 'Billing details updated',
            'manual_access_granted' => 'Manual access granted',
            'demo_enabled' => 'Demo enabled',
            'demo_reset' => 'Demo reset',
            'demo_reset_configured' => 'Demo reset configured',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            'reactivated' => 'Reactivated',
        ];
    }
}
