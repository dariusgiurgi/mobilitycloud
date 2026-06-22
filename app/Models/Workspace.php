<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Workspace extends Model
{
    protected $fillable = [
        'name', 'slug', 'plan',
        'billing_name', 'billing_vat', 'billing_address', 'billing_country',
        'currencies', 'document_settings', 'document_logo_path', 'trial_ends_at',
    ];

    protected $casts = [
        'currencies' => 'array',
        'document_settings' => 'array',
        'trial_ends_at' => 'datetime',
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

    public function roleFor(User $user): ?string
    {
        $member = $this->users()->where('user_id', $user->id)->first();

        return $member?->pivot->role;
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($this->roleFor($user), ['owner', 'admin', 'member'], true);
    }

    public function canManageMembersBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($this->roleFor($user), ['owner', 'admin'], true);
    }
}
