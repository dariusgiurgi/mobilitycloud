<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobilityFeedbackCampaign extends Model
{
    protected $fillable = [
        'project_mobility_id', 'feedback_form_id', 'created_by', 'title', 'public_token', 'form_snapshot', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'form_snapshot' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function mobility(): BelongsTo
    {
        return $this->belongsTo(ProjectMobility::class, 'project_mobility_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'feedback_form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(MobilityFeedbackResponse::class);
    }

    public function hasActiveLink(): bool
    {
        return filled($this->public_token)
            && $this->opened_at !== null
            && $this->closed_at === null;
    }

    public function questions(): array
    {
        return array_values(data_get($this->form_snapshot, 'questions', []));
    }

    public function canBeAccessedBy(?User $user): bool
    {
        return $this->mobility?->project?->canBeAccessedBy($user) ?? false;
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $this->mobility?->project?->canManageProjectModule($user, 'mobility') ?? false;
    }
}
