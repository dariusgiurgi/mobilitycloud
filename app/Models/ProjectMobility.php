<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMobility extends Model
{
    public const PARTICIPATION_STATUSES = [
        'planned' => 'Planned',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'project_id', 'name', 'start_date', 'end_date', 'destination_country', 'host_organisation', 'participating_organisations', 'workspace_data', 'sort_order',
        'participant_registration_token', 'participant_registration_opened_at', 'participant_registration_closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'workspace_data' => 'array',
        'participating_organisations' => 'array',
        'participant_registration_opened_at' => 'datetime',
        'participant_registration_closed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'mobility_participant')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function feedbackCampaigns(): HasMany
    {
        return $this->hasMany(MobilityFeedbackCampaign::class);
    }

    public function hasActiveParticipantRegistrationLink(): bool
    {
        return filled($this->participant_registration_token)
            && $this->participant_registration_opened_at !== null
            && $this->participant_registration_closed_at === null;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $mobility) => $mobility->syncLegacyProjectDates());
        static::deleted(fn (self $mobility) => $mobility->syncLegacyProjectDates());
    }

    private function syncLegacyProjectDates(): void
    {
        $project = $this->project()->first();

        if (! $project) {
            return;
        }

        $firstMobility = $project->mobilities()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $project->forceFill([
            'mobility_start_date' => $firstMobility?->start_date,
            'mobility_end_date' => $firstMobility?->end_date,
        ])->saveQuietly();
    }
}
