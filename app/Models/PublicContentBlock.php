<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicContentBlock extends Model
{
    protected $fillable = [
        'user_id', 'origin_workspace_id', 'title', 'category', 'ka_action',
        'language', 'body', 'tags', 'is_proven', 'source_note', 'import_count', 'likes_count',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_proven'    => 'boolean',
        'import_count' => 'integer',
        'likes_count'  => 'integer',
    ];

    // Email-ul contului de sistem care detine blocurile oficiale.
    public const OFFICIAL_EMAIL = 'official@mobilitycloud.eu';

    // Reutilizam aceleasi liste ca la blocurile personale, ca sa fie consecvent.
    public const CATEGORIES = ContentBlock::CATEGORIES;
    public const KA_ACTIONS = ContentBlock::KA_ACTIONS;
    public const LANGUAGES  = ContentBlock::LANGUAGES;

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function originWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'origin_workspace_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PublicBlockLike::class, 'public_content_block_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PublicBlockReport::class, 'public_content_block_id');
    }

    /** Userul dat a raportat deja acest bloc? */
    public function isReportedBy(?User $user): bool
    {
        if (! $user) return false;
        return $this->reports()->where('user_id', $user->id)->exists();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Userul curent este autorul acestui bloc? */
    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->user_id === $user->id;
    }

    /** Bloc oficial (creat de contul de sistem)? */
    public function isOfficial(): bool
    {
        return $this->author && $this->author->email === self::OFFICIAL_EMAIL;
    }

    /** Userul dat a dat like acestui bloc? */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /** Comuta like-ul pentru user si actualizeaza contorul. Returneaza true daca acum e liked. */
    public function toggleLike(User $user): bool
    {
        $existing = $this->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $this->decrement('likes_count');
            return false;
        }

        $this->likes()->create(['user_id' => $user->id]);
        $this->increment('likes_count');
        return true;
    }

    /**
     * Scor de relevanta: like-uri + importuri (semnal mai puternic) + un bonus
     * de prospetime care scade in timp. Folosit pentru sortarea "Relevant".
     */
    public function getRelevanceScoreAttribute(): float
    {
        $ageDays = $this->created_at ? $this->created_at->diffInDays(now()) : 999;
        $freshness = max(0, 30 - $ageDays) / 30; // 1.0 azi → 0 dupa 30 de zile

        return ($this->likes_count * 2) + ($this->import_count * 3) + ($freshness * 5);
    }
}