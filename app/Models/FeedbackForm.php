<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackForm extends Model
{
    public const QUESTION_TYPES = [
        'rating' => 'Rating (1–5)',
        'single_choice' => 'One choice',
        'multiple_choice' => 'Multiple choices',
        'yes_no' => 'Yes / no',
        'short_text' => 'Short answer',
        'long_text' => 'Long answer',
    ];

    protected $fillable = [
        'owner_id', 'name', 'description', 'intro_text', 'thank_you_text', 'questions', 'is_archived',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_archived' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MobilityFeedbackCampaign::class);
    }

    public function scopeOwnedBy(Builder $query, ?User $user): Builder
    {
        return $query->where('owner_id', $user?->id ?: 0);
    }
}
