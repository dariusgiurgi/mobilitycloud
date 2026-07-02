<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\WorkspaceAccess;

class Workspace extends Model
{
    protected $fillable = [
        'name', 'slug', 'plan', 'subscription_status',
        'billing_name', 'billing_vat', 'billing_address', 'billing_country',
        'billing_interval', 'billing_amount', 'billing_currency', 'billing_reference',
        'billing_provider', 'billing_provider_customer_id', 'billing_provider_subscription_id',
        'trial_ending_alerted_at', 'trial_expired_alerted_at', 'subscription_ending_alerted_at',
        'subscription_expired_alerted_at', 'manual_access_ending_alerted_at', 'demo_reset_stale_alerted_at',
        'currencies', 'document_settings', 'feature_flags', 'plan_limits', 'document_logo_path', 'trial_ends_at',
        'subscription_ends_at', 'is_suspended', 'access_override_ends_at', 'access_override_reason',
        'access_override_granted_by', 'suspension_category', 'suspension_reason', 'suspended_at', 'suspended_by',
        'is_internal', 'demo_reset_frequency', 'demo_last_reset_at', 'internal_notes',
    ];

    protected $casts = [
        'currencies' => 'array',
        'document_settings' => 'array',
        'feature_flags' => 'array',
        'plan_limits' => 'array',
        'billing_amount' => 'decimal:2',
        'trial_ending_alerted_at' => 'datetime',
        'trial_expired_alerted_at' => 'datetime',
        'subscription_ending_alerted_at' => 'datetime',
        'subscription_expired_alerted_at' => 'datetime',
        'manual_access_ending_alerted_at' => 'datetime',
        'demo_reset_stale_alerted_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'access_override_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'demo_last_reset_at' => 'datetime',
        'is_suspended' => 'boolean',
        'is_internal' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace) {
            if (empty($workspace->slug)) {
                $base = Str::slug($workspace->name) ?: 'workspace';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $workspace->slug = $slug;
            }
        });

        static::deleting(function (Workspace $workspace): void {
            $workspace->projects()->withTrashed()->get()->each->forceDelete();
            if ($workspace->document_logo_path) {
                Storage::disk('local')->delete($workspace->document_logo_path);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function accessibleProjectsFor(?User $user)
    {
        return $this->projects()->accessibleTo($user, $this);
    }

    public function documentSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->document_settings, $key, $default);
    }

    public function documentLogoDataUri(): ?string
    {
        if (! $this->document_logo_path || ! Storage::disk('local')->exists($this->document_logo_path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($this->document_logo_path);
        $extension = strtolower(pathinfo($this->document_logo_path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function savedCalculations(): HasMany
    {
        return $this->hasMany(SavedCalculation::class);
    }

    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(PlatformSubscriptionEvent::class);
    }

    public function latestSubscriptionEvent(): HasOne
    {
        return $this->hasOne(PlatformSubscriptionEvent::class)->latestOfMany();
    }

    public function platformNotes(): HasMany
    {
        return $this->hasMany(PlatformWorkspaceNote::class);
    }

    public function accessOverrideGrantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'access_override_granted_by');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function roleFor(User $user): ?string
    {
        $member = $this->users()->where('user_id', $user->id)->first();

        return $member?->pivot->role;
    }

    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', 'owner')->orderBy('name')->first();
    }

    public function subscriptionStatusLabel(): string
    {
        return match ($this->subscription_status ?: 'active') {
            'demo' => 'Demo',
            'trial' => 'Trial',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            default => 'Active',
        };
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! WorkspaceAccess::isReadOnly($this)
            && in_array($this->roleFor($user), ['owner', 'admin', 'member'], true);
    }

    public function canManageMembersBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! WorkspaceAccess::isReadOnly($this)
            && in_array($this->roleFor($user), ['owner', 'admin'], true);
    }
}
