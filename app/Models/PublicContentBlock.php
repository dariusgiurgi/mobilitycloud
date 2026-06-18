<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicContentBlock extends Model
{
    protected $fillable = [
        'user_id', 'origin_workspace_id', 'title', 'category', 'ka_action',
        'language', 'body', 'tags', 'is_proven', 'source_note', 'import_count',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_proven'    => 'boolean',
        'import_count' => 'integer',
    ];

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

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Userul curent este autorul acestui bloc? */
    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->user_id === $user->id;
    }
}