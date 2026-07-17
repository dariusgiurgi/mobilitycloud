<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'notification_preferences', 'role',
        'plan', 'subscription_status', 'trial_ends_at', 'subscription_ends_at', 'feature_flags',
        'plan_limits', 'currencies', 'document_settings', 'access_override_ends_at', 'access_override_reason',
        'access_override_granted_by', 'billing_interval', 'billing_amount', 'billing_currency', 'billing_reference',
        'billing_provider', 'billing_provider_customer_id', 'billing_provider_subscription_id',
        'billing_name', 'billing_vat', 'billing_country', 'billing_address',
        'demo_reset_frequency', 'demo_last_reset_at', 'trial_ending_alerted_at', 'trial_expired_alerted_at',
        'subscription_ending_alerted_at', 'subscription_expired_alerted_at', 'manual_access_ending_alerted_at',
        'demo_reset_stale_alerted_at',
        'is_suspended', 'suspension_category', 'suspension_reason', 'suspended_at', 'suspended_by',
        'archived_at', 'archived_by', 'archived_reason', 'must_change_password', 'support_notes', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'feature_flags' => 'array',
            'plan_limits' => 'array',
            'currencies' => 'array',
            'document_settings' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'access_override_ends_at' => 'datetime',
            'billing_amount' => 'decimal:2',
            'demo_last_reset_at' => 'datetime',
            'trial_ending_alerted_at' => 'datetime',
            'trial_expired_alerted_at' => 'datetime',
            'subscription_ending_alerted_at' => 'datetime',
            'subscription_expired_alerted_at' => 'datetime',
            'manual_access_ending_alerted_at' => 'datetime',
            'demo_reset_stale_alerted_at' => 'datetime',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function supportNotes(): HasMany
    {
        return $this->hasMany(PlatformSupportNote::class);
    }

    public function authoredSupportNotes(): HasMany
    {
        return $this->hasMany(PlatformSupportNote::class, 'author_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function accessOverrideGrantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'access_override_granted_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function getPlatformRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_PLATFORM_OWNER => 'Platform owner',
            self::ROLE_PLATFORM_ADMIN => 'Platform admin',
            self::ROLE_ADMIN => 'Legacy admin',
            self::ROLE_SUPERVISOR => 'Legacy supervisor',
            default => 'User',
        };
    }

    public function wantsNotification(string $type): bool
    {
        return (bool) data_get($this->notification_preferences, $type, true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'platform' => $this->isPlatformAdmin(),
            'admin' => true,
            default => true,
        };
    }

    public const ROLE_USER = 'user';

    public const ROLE_PLATFORM_OWNER = 'platform_owner';

    public const ROLE_PLATFORM_ADMIN = 'platform_admin';

    /** @deprecated Use ROLE_PLATFORM_OWNER for global platform ownership. */
    public const ROLE_ADMIN = 'admin';

    /** @deprecated Use ROLE_PLATFORM_ADMIN for global platform administration. */
    public const ROLE_SUPERVISOR = 'supervisor';

    public function isAdmin(): bool
    {
        return $this->isPlatformOwner();
    }

    public function isPlatformOwner(): bool
    {
        return in_array($this->role, [self::ROLE_PLATFORM_OWNER, self::ROLE_ADMIN], true);
    }

    public function isPlatformAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_PLATFORM_OWNER,
            self::ROLE_PLATFORM_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role, [self::ROLE_PLATFORM_ADMIN, self::ROLE_SUPERVISOR], true);
    }

    public function canManagePlatformAdmins(): bool
    {
        return $this->isPlatformOwner();
    }

    public static function platformRoleOptions(): array
    {
        return [
            self::ROLE_PLATFORM_OWNER => 'Platform owner',
            self::ROLE_PLATFORM_ADMIN => 'Platform admin',
        ];
    }

    /** Are drepturi de administrare interna a platformei? */
    public function canModerate(): bool
    {
        return $this->isPlatformAdmin();
    }

    public function isUnlimitedAccount(): bool
    {
        return $this->plan === 'unlimited'
            || data_get($this->plan_limits, 'unlimited') === true
            || in_array('unlimited', $this->feature_flags ?: [], true);
    }

    public function hasBillingDetails(): bool
    {
        return filled($this->billing_name)
            && filled($this->billing_country)
            && filled($this->billing_address);
    }

    public function billingDetailsForDisplay(): array
    {
        return [
            'Billing name' => $this->billing_name ?: '—',
            'VAT / registration' => $this->billing_vat ?: '—',
            'Country' => $this->billing_country ?: '—',
            'Address' => $this->billing_address ?: '—',
            'Email' => $this->email ?: '—',
        ];
    }
}
