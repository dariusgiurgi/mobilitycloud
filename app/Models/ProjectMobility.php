<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMobility extends Model
{
    protected $fillable = [
        'project_id', 'name', 'start_date', 'end_date', 'destination_country', 'host_organisation', 'workspace_data', 'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'workspace_data' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
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
