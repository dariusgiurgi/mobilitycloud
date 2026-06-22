<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlock extends Model
{
    protected $fillable = [
        'workspace_id', 'title', 'category', 'ka_action', 'language',
        'body', 'tags', 'is_proven', 'source_note', 'usage_count', 'imported_from_public_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_proven' => 'boolean',
        'usage_count' => 'integer',
    ];

    public const CATEGORIES = [
        'organisation' => 'Organisation background',
        'partners' => 'Partners',
        'needs' => 'Needs & objectives',
        'impact' => 'Impact',
        'methodology' => 'Methodology & activities',
        'inclusion' => 'Inclusion (fewer opportunities)',
        'safety' => 'Safety & protection',
        'evaluation' => 'Evaluation',
        'dissemination' => 'Dissemination',
        'sustainability' => 'Sustainability',
        'environment' => 'Green practices',
        'recognition' => 'Recognition (Youthpass)',
        'communication' => 'Communication',
        'virtual' => 'Virtual / blended',
        'other' => 'Other',
    ];

    // Keys match the ApplicationTemplates keys (lowercase) so the picker can
    // filter library blocks by the project's action.
    public const KA_ACTIONS = [
        'any' => 'Any action',
        'ka122' => 'KA122 — Short-term mobility',
        'ka151-you' => 'KA151-YOU — Accredited youth mobility',
        'ka152' => 'KA152 — Youth exchanges (legacy)',
        'ka152-you' => 'KA152-YOU — Youth exchanges',
        'ka153-you' => 'KA153-YOU — Mobility of youth workers',
        'ka154-you' => 'KA154-YOU — Youth participation activities',
        'ka155-you' => 'KA155-YOU — DiscoverEU Inclusion Action',
        'ka210' => 'KA210 — Small-scale partnerships',
        'ka220' => 'KA220 — Cooperation partnerships',
    ];

    public const LANGUAGES = [
        'en' => 'English',
        'ro' => 'Romanian',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
