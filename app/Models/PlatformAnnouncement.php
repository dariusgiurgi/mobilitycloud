<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    public const SEVERITIES = [
        'info' => 'Info',
        'warning' => 'Warning',
        'maintenance' => 'Maintenance',
        'critical' => 'Critical',
    ];

    public const AUDIENCES = [
        'all' => 'All users',
        'platform_admins' => 'Platform admins',
        'client_users' => 'Client users',
        'plans' => 'Selected plans',
        'selected_users' => 'Selected users',
    ];

    public const DELIVERY_TYPES = [
        'banner' => 'Banner only',
        'notification' => 'Notification only',
        'both' => 'Banner + notification',
    ];

    protected $fillable = [
        'created_by', 'title', 'message', 'severity', 'delivery_type', 'audience', 'plans', 'target_user_ids',
        'starts_at', 'ends_at', 'is_active', 'is_dismissible', 'notification_sent_at', 'notification_sent_count',
    ];

    protected $casts = [
        'plans' => 'array',
        'target_user_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'notification_sent_at' => 'datetime',
        'notification_sent_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeActiveBanner(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereIn('delivery_type', ['banner', 'both']);
    }

    public function isVisibleFor(?User $user): bool
    {
        return match ($this->audience) {
            'platform_admins' => $user?->isPlatformAdmin() ?? false,
            'client_users' => ! ($user?->isPlatformAdmin() ?? false),
            'plans' => in_array($user?->plan, $this->plans ?? [], true),
            'selected_users' => in_array((int) $user?->id, collect($this->target_user_ids ?? [])->map(fn ($id): int => (int) $id)->all(), true),
            default => true,
        };
    }

    public function isBanner(): bool
    {
        return in_array($this->delivery_type ?: 'banner', ['banner', 'both'], true);
    }

    public function sendsNotification(): bool
    {
        return in_array($this->delivery_type ?: 'banner', ['notification', 'both'], true);
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'critical' => '#991b1b',
            'maintenance' => '#92400e',
            'warning' => '#a16207',
            default => '#1d4ed8',
        };
    }
}
