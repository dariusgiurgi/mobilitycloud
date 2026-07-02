<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'current_workspace_id', 'notification_preferences', 'role',
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
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
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

    public function getTenants(Panel $panel): Collection
    {
        return $this->workspaces;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->workspaces()->whereKey($tenant)->exists();
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
}
