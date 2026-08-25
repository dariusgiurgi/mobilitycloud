<?php

namespace App\Models;

use App\Support\DemoWorkspace;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use LogicException;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    use HasFactory, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable, SoftDeletes;

    private const BILLING_DETAIL_PLACEHOLDERS = [
        '',
        '-',
        '—',
        'n/a',
        'na',
        'none',
        'null',
        'not set',
        'not provided',
        'to be completed',
        'to complete',
        'tbd',
        'test',
        'testing',
        'demo',
        'de completat',
        'necompletat',
        'fara date',
        'fără date',
    ];

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
        'account_deletion_status', 'account_deletion_requested_at', 'account_deletion_requested_by',
        'account_deletion_project_disposition', 'account_deletion_transfer_account_id',
        'account_deletion_started_at', 'account_deletion_failure',
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
            'account_deletion_requested_at' => 'datetime',
            'account_deletion_requested_by' => 'integer',
            'account_deletion_transfer_account_id' => 'integer',
            'account_deletion_started_at' => 'datetime',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (! $user->isForceDeleting()) {
                return;
            }

            if ($user->ownedProjects()->withTrashed()->exists()) {
                throw new LogicException('Owned projects must be transferred or purged before permanently deleting an account.');
            }

            $logoPath = data_get($user->document_settings, 'logo_path');

            if (filled($logoPath)) {
                Storage::disk('local')->delete((string) $logoPath);
            }
        });
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

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function feedbackForms(): HasMany
    {
        return $this->hasMany(FeedbackForm::class, 'owner_id');
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

    public const ACCOUNT_DELETION_QUEUED = 'queued';

    public const ACCOUNT_DELETION_PROCESSING = 'processing';

    public const ACCOUNT_DELETION_FAILED = 'failed';

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

    public function hasAccountDeletionInProgress(): bool
    {
        return in_array($this->account_deletion_status, [
            self::ACCOUNT_DELETION_QUEUED,
            self::ACCOUNT_DELETION_PROCESSING,
        ], true);
    }

    public function hasFailedAccountDeletion(): bool
    {
        return $this->account_deletion_status === self::ACCOUNT_DELETION_FAILED;
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

    /**
     * Keep database-level account reporting in exact agreement with
     * isUnlimitedAccount(). JSON predicates avoid fragile text matching.
     */
    public function scopeWithUnlimitedAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('plan', 'unlimited')
                ->orWhere('plan_limits->unlimited', true)
                ->orWhereJsonContains('feature_flags', 'unlimited');
        });
    }

    public function scopeWithoutUnlimitedAccess(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('plan')
                ->orWhere('plan', '!=', 'unlimited'))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('plan_limits->unlimited')
                ->orWhere('plan_limits->unlimited', '!=', true))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('feature_flags')
                ->orWhereJsonDoesntContain('feature_flags', 'unlimited'));
    }

    /**
     * SQL equivalent of AccountAccess::isReadOnly() for aggregate reporting.
     * Active owner overrides are deliberately evaluated before all lock rules.
     */
    public function scopeReadOnlyAccounts(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('access_override_reason')
                    ->orWhereRaw("TRIM(access_override_reason) = ''")
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereNotNull('access_override_ends_at')
                            ->where('access_override_ends_at', '<=', now());
                    });
            })
            ->where(function (Builder $query): void {
                $query
                    ->where('email', DemoWorkspace::VISITOR_EMAIL)
                    ->orWhere('is_suspended', true)
                    ->orWhere('subscription_status', 'suspended')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->withoutUnlimitedAccess()
                            ->where(function (Builder $query): void {
                                $query
                                    ->where('subscription_status', 'expired')
                                    ->orWhere(function (Builder $query): void {
                                        $query
                                            ->whereNotNull('subscription_ends_at')
                                            ->where('subscription_ends_at', '<', now());
                                    });
                            });
                    });
            });
    }

    public function hasBillingDetails(): bool
    {
        return self::isMeaningfulBillingDetail($this->billing_name, 2)
            && self::isMeaningfulBillingDetail($this->billing_country, 2)
            && self::isMeaningfulBillingDetail($this->billing_address, 8);
    }

    public function scopeWithCompleteBillingDetails(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $query): Builder => self::addMeaningfulBillingDetailConstraint($query, 'billing_name', 2))
            ->where(fn (Builder $query): Builder => self::addMeaningfulBillingDetailConstraint($query, 'billing_country', 2))
            ->where(fn (Builder $query): Builder => self::addMeaningfulBillingDetailConstraint($query, 'billing_address', 8));
    }

    public function scopeMissingBillingDetails(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            self::addMissingBillingDetailConstraint($query, 'billing_name', 2);
            self::addMissingBillingDetailConstraint($query, 'billing_country', 2);
            self::addMissingBillingDetailConstraint($query, 'billing_address', 8);
        });
    }

    private static function isMeaningfulBillingDetail(mixed $value, int $minLength): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        if (mb_strlen($normalized) < $minLength) {
            return false;
        }

        return ! in_array(mb_strtolower($normalized), self::BILLING_DETAIL_PLACEHOLDERS, true);
    }

    private static function addMeaningfulBillingDetailConstraint(Builder $query, string $column, int $minLength): Builder
    {
        $placeholders = implode(',', array_fill(0, count(self::BILLING_DETAIL_PLACEHOLDERS), '?'));

        return $query
            ->whereRaw("LENGTH(TRIM(COALESCE({$column}, ''))) >= ?", [$minLength])
            ->whereRaw("LOWER(TRIM(COALESCE({$column}, ''))) NOT IN ({$placeholders})", self::BILLING_DETAIL_PLACEHOLDERS);
    }

    private static function addMissingBillingDetailConstraint(Builder $query, string $column, int $minLength): void
    {
        $placeholders = implode(',', array_fill(0, count(self::BILLING_DETAIL_PLACEHOLDERS), '?'));

        $query
            ->orWhereRaw("LENGTH(TRIM(COALESCE({$column}, ''))) < ?", [$minLength])
            ->orWhereRaw("LOWER(TRIM(COALESCE({$column}, ''))) IN ({$placeholders})", self::BILLING_DETAIL_PLACEHOLDERS);
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
