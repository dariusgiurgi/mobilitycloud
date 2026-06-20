<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_UPLOAD = 'uploaded';

    public const CATEGORIES = [
        'grant_agreement' => 'Grant agreement',
        'approved_application' => 'Approved application',
        'mandate' => 'Partner mandate',
        'partnership_agreement' => 'Partnership agreement',
        'activity_agenda' => 'Activity agenda',
        'report' => 'Report',
        'other' => 'Other',
    ];

    protected $fillable = [
        'project_id', 'type', 'category', 'title', 'activity_title', 'activity_date', 'location',
        'document_date', 'notes', 'metadata', 'file_path', 'file_disk', 'file_name', 'file_size',
        'signed_path', 'signed_disk', 'signed_name', 'signed_size', 'generated_at', 'signed_at',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'document_date' => 'date',
        'metadata' => 'array',
        'file_size' => 'integer',
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

    public function hasFile(): bool
    {
        return $this->file_path
            && Storage::disk($this->file_disk ?: 'local')->exists($this->file_path);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Other';
    }

    public function humanFileSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1).' MB';
        }

        return number_format(max(0, $bytes) / 1024, 0).' KB';
    }

    public function statusLabel(): string
    {
        return $this->hasSignedCopy() ? 'Signed' : 'Awaiting signatures';
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectDocument $document): void {
            if ($document->hasFile()) {
                Storage::disk($document->file_disk ?: 'local')->delete($document->file_path);
            }
            if ($document->hasSignedCopy()) {
                Storage::disk($document->signed_disk ?: 'local')->delete($document->signed_path);
            }
        });
    }
}
