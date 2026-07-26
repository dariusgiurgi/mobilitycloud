<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_EXPENSE_REPORT = 'expense_report';

    public const TYPE_UPLOAD = 'uploaded';

    public const MOBILITY_CATEGORIES = [
        'mobility_plan' => 'Mobility plan',
        'mobility_material' => 'Mobility material / worksheet',
        'mobility_output' => 'Mobility output',
        'mobility_photo_video' => 'Photo / video evidence',
        'mobility_other' => 'Other mobility file',
    ];

    public const CATEGORIES = [
        'grant_agreement' => 'Grant agreement',
        'approved_application' => 'Approved application',
        'mandate' => 'Partner mandate',
        'partnership_agreement' => 'Partnership agreement',
        'activity_agenda' => 'Activity agenda',
        'mobility_plan' => 'Mobility plan',
        'mobility_material' => 'Mobility material / worksheet',
        'mobility_output' => 'Mobility output',
        'mobility_photo_video' => 'Photo / video evidence',
        'mobility_other' => 'Other mobility file',
        'dissemination_evidence' => 'Dissemination evidence',
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

    public function isImageFile(): bool
    {
        return in_array($this->fileExtension(), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function fileExtension(): string
    {
        $name = strtolower((string) ($this->file_name ?: $this->file_path ?: $this->signed_name ?: $this->signed_path));

        return trim((string) pathinfo($name, PATHINFO_EXTENSION));
    }

    public function fileKind(): string
    {
        if ($this->type === self::TYPE_ATTENDANCE || $this->type === self::TYPE_EXPENSE_REPORT) {
            return 'pdf';
        }

        return match ($this->fileExtension()) {
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'image',
            'pdf' => 'pdf',
            'doc', 'docx', 'odt', 'rtf' => 'word',
            'xls', 'xlsx', 'csv', 'ods' => 'excel',
            'ppt', 'pptx', 'odp' => 'powerpoint',
            'zip', 'rar', '7z' => 'archive',
            default => 'file',
        };
    }

    public function fileBadgeLabel(): string
    {
        return match ($this->fileKind()) {
            'image' => 'IMG',
            'pdf' => 'PDF',
            'word' => 'W',
            'excel' => 'E',
            'powerpoint' => 'P',
            'archive' => 'ZIP',
            default => strtoupper($this->fileExtension() ?: 'FILE'),
        };
    }

    public function fileAccent(): string
    {
        return match ($this->fileKind()) {
            'image' => '#2563eb',
            'pdf' => '#dc2626',
            'word' => '#2563eb',
            'excel' => '#16a34a',
            'powerpoint' => '#ea580c',
            'archive' => '#7c3aed',
            default => '#64748b',
        };
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
