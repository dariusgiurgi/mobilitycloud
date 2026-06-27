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
        'workspace_users' => 'Workspace users',
        'plans' => 'Selected plans',
        'workspaces' => 'Selected workspaces',
    ];

    protected $fillable = [
        'created_by', 'title', 'message', 'severity', 'audience', 'plans', 'workspace_ids',
        'starts_at', 'ends_at', 'is_active', 'is_dismissible',
    ];

    protected $casts = [
        'plans' => 'array',
        'workspace_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
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

    public function isVisibleFor(?User $user, ?Workspace $workspace): bool
    {
        return match ($this->audience) {
            'platform_admins' => $user?->isPlatformAdmin() ?? false,
            'workspace_users' => ! ($user?->isPlatformAdmin() ?? false),
            'plans' => in_array($workspace?->plan, $this->plans ?? [], true),
            'workspaces' => in_array($workspace?->id, $this->workspace_ids ?? [], true),
            default => true,
        };
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
