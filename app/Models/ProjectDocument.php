<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    public const TYPE_ATTENDANCE = 'attendance';

    protected $fillable = [
        'project_id', 'type', 'title', 'activity_title', 'activity_date', 'location', 'metadata',
        'signed_path', 'signed_disk', 'signed_name', 'signed_size', 'generated_at', 'signed_at',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'metadata' => 'array',
        'signed_size' => 'integer',
        'generated_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hasSignedCopy(): bool
    {
        return $this->signed_path
            && Storage::disk($this->signed_disk ?: 'local')->exists($this->signed_path);
    }

    public function statusLabel(): string
    {
        return $this->hasSignedCopy() ? 'Signed' : 'Awaiting signatures';
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectDocument $document): void {
            if ($document->hasSignedCopy()) {
                Storage::disk($document->signed_disk ?: 'local')->delete($document->signed_path);
            }
        });
    }
}
