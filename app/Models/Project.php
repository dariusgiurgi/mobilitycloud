<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    public const DEFAULT_BUDGET_LINES = [
        ['title' => 'Travel',                 'emoji' => '✈️', 'color' => '#3b82f6', 'sort_order' => 0],
        ['title' => 'Individual Support',     'emoji' => '🙋', 'color' => '#22c55e', 'sort_order' => 1],
        ['title' => 'Organisational Support', 'emoji' => '🏢', 'color' => '#8b5cf6', 'sort_order' => 2],
        ['title' => 'Inclusion Support',      'emoji' => '🤝', 'color' => '#ec4899', 'sort_order' => 3],
        ['title' => 'Exceptional Support',    'emoji' => '⚡', 'color' => '#f59e0b', 'sort_order' => 4],
    ];

    protected $fillable = [
        'workspace_id', 'access_mode', 'name', 'acronym', 'grant_ref', 'ka_action', 'description', 'status',
        'total_budget', 'approved_budget', 'first_tranche_pct', 'withholding_tax_rate',
        'is_activated', 'activated_at', 'activation_tier', 'activation_snapshot', 'activation_payment_id',
        'expense_prefix', 'expense_pad_length',
        'start_date', 'end_date', 'mobility_start_date', 'mobility_end_date', 'partner_org', 'partner_orgs', 'notes',
        'action_data',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'approved_budget' => 'decimal:2',
        'is_activated' => 'boolean',
        'activated_at' => 'datetime',
        'activation_snapshot' => 'array',
        'partner_orgs' => 'array',
        'action_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'mobility_start_date' => 'date',
        'mobility_end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            foreach (self::DEFAULT_BUDGET_LINES as $line) {
                $project->budgetLines()->create([
                    'title' => $line['title'], 'emoji' => $line['emoji'],
                    'color' => $line['color'], 'allocated_budget' => 0, 'sort_order' => $line['sort_order'],
                ]);
            }
        });

        static::deleting(function (Project $project): void {
            if (! $project->isForceDeleting()) {
                return;
            }

            $project->participants()->get()->each->delete();
            $project->documents()->get()->each->delete();
            $project->budgetLines()->get()->each->delete();
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function scopeAccessibleTo(Builder $query, ?User $user, ?Workspace $workspace = null): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $workspace ??= $this->workspace;
        $role = $workspace?->roleFor($user);

        if (in_array($role, ['owner', 'admin'], true)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('access_mode', 'workspace')
                ->orWhereHas('members', fn (Builder $members) => $members->whereKey($user->id));
        });
    }

    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user || ! $this->workspace?->users()->whereKey($user->id)->exists()) {
            return false;
        }

        if (in_array($this->workspace->roleFor($user), ['owner', 'admin'], true)) {
            return true;
        }

        return ($this->access_mode ?: 'workspace') === 'workspace'
            || $this->members()->whereKey($user->id)->exists();
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function applicationSections(): HasMany
    {
        return $this->hasMany(ProjectApplicationSection::class);
    }

    public function applicationVersions(): HasMany
    {
        return $this->hasMany(ProjectApplicationVersion::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ProjectActivityLog::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->canBeAccessedBy($user)
            && ($this->workspace?->canBeManagedBy($user) ?? false);
    }

    /**
     * The figure all spending is measured against: the approved grant once the
     * funder has confirmed it, otherwise the requested total.
     */
    public function getEffectiveBudgetAttribute(): float
    {
        $approved = (float) $this->approved_budget;

        return $approved > 0 ? $approved : (float) $this->total_budget;
    }

    /**
     * Partner organisations as a clean list, falling back to the legacy single
     * field if the new list has not been populated yet.
     *
     * @return array<int, array{name: string, country: ?string, oid: ?string}>
     */
    public function getPartnersAttribute(): array
    {
        $list = $this->partner_orgs;

        if (is_array($list) && count($list) > 0) {
            return array_values(array_filter($list, fn ($p) => ! empty($p['name'])));
        }

        if (! empty($this->partner_org)) {
            return [['name' => $this->partner_org, 'country' => null, 'oid' => null]];
        }

        return [];
    }

    public function getSpentAttribute(): float
    {
        return (float) $this->budgetLines->sum(fn ($bl) => $bl->expenses->sum('amount_eur'));
    }

    public function getRemainingAttribute(): float
    {
        return $this->effective_budget - $this->spent;
    }

    public function getProgressAttribute(): int
    {
        $budget = $this->effective_budget;
        if ($budget <= 0) {
            return 0;
        }

        return min(100, (int) round($this->spent / $budget * 100));
    }

    // ─── Lifecycle ───
    // `status` stays a plain string column (no enum cast) so existing string
    // comparisons in blades keep working. New code uses statusEnum().
    public function statusEnum(): ProjectStatus
    {
        return ProjectStatus::tryFrom($this->status ?? 'writing') ?? ProjectStatus::Writing;
    }

    public function isWritingStage(): bool
    {
        return $this->statusEnum()->isWritingStage();
    }

    public function isManagementStage(): bool
    {
        return $this->statusEnum()->isManagementStage();
    }
}
