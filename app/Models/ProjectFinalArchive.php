<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectFinalArchive extends Model
{
    public const CATEGORY_KEYS = [
        'project_data',
        'application',
        'participants',
        'budget',
        'agreements',
        'generated_records',
        'project_files',
        'mobility',
        'dissemination',
        'feedback',
    ];

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'project_id', 'requested_by', 'uuid', 'status', 'selection', 'selection_hash',
        'filename', 'disk', 'path', 'size', 'sha256', 'failure_message', 'started_at',
        'completed_at', 'expires_at', 'downloaded_at', 'download_count',
    ];

    protected function casts(): array
    {
        return [
            'selection' => 'array',
            'size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'download_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProjectFinalArchive $archive): void {
            $archive->uuid ??= (string) Str::uuid();
        });

        static::deleting(function (ProjectFinalArchive $archive): void {
            $archive->deleteStoredFile();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY
            && $this->expires_at?->isFuture()
            && filled($this->path)
            && Storage::disk($this->disk ?: 'local')->exists($this->path);
    }

    public function hasExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at?->isPast() ?? false);
    }

    public function deleteStoredFile(): void
    {
        if (filled($this->path)) {
            Storage::disk($this->disk ?: 'local')->delete($this->path);
        }
    }

    public function expire(): void
    {
        $this->deleteStoredFile();
        $this->forceFill([
            'status' => self::STATUS_EXPIRED,
            'disk' => null,
            'path' => null,
        ])->save();
    }
}
