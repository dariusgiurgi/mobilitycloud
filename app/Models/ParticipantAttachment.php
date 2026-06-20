<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ParticipantAttachment extends Model
{
    protected $fillable = [
        'participant_id', 'type', 'path', 'disk', 'original_name', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    // Tipurile de documente (cheie => eticheta).
    public const TYPES = [
        'gdpr' => 'GDPR consent',
        'parental' => 'Parental consent',
        'agreement' => 'Participant agreement',
        'enrollment' => 'Enrollment form',
        'id_copy' => 'ID / passport copy',
        'insurance' => 'Medical insurance',
        'other' => 'Other',
    ];

    // Prefixele pentru numele de fisier generat per tip.
    public const FILE_PREFIXES = [
        'gdpr' => 'gdpr_consent',
        'parental' => 'parental_consent',
        'agreement' => 'participant_agreement',
        'enrollment' => 'enrollment_form',
        'id_copy' => 'id_copy',
        'insurance' => 'medical_insurance',
        'other' => 'document',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function exists(): bool
    {
        return $this->path && Storage::disk($this->disk ?: 'local')->exists($this->path);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }

    /** Sterge fisierul de pe disk cand se sterge randul. */
    protected static function booted(): void
    {
        static::deleting(function (ParticipantAttachment $att) {
            if ($att->exists()) {
                Storage::disk($att->disk ?: 'local')->delete($att->path);
            }
        });
    }
}
