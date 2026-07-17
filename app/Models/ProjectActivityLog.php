<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivityLog extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'event',
        'subject_type', 'subject_id', 'description', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function icon(): string
    {
        return match ($this->event) {
            'created' => 'heroicon-o-plus',
            'deleted' => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-uturn-left',
            'status_changed' => 'heroicon-o-arrow-path',
            default => 'heroicon-o-pencil-square',
        };
    }

    public function color(): string
    {
        return match ($this->event) {
            'created' => '#059669',
            'deleted' => '#dc2626',
            'restored' => '#059669',
            'status_changed' => '#7c3aed',
            default => '#4f46e5',
        };
    }
}
