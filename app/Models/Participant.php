<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'project_id', 'complete_name', 'first_name', 'last_name', 'birth_date', 'nationality', 'gender',
        'partner_organisation', 'country', 'role',
        'email', 'phone', 'address',
        'medical_conditions', 'allergies', 'dietary_restrictions', 'special_needs', 'fewer_opportunities',
        'guardian_name', 'guardian_contact', 'gdpr_consented_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'fewer_opportunities' => 'boolean',
        'gdpr_consented_at' => 'datetime',
    ];

    // Rolurile disponibile (cheie => eticheta).
    public const ROLES = [
        'participant' => 'Participant',
        'group_leader' => 'Group leader',
        'facilitator' => 'Facilitator',
        'accompanying_person' => 'Accompanying person',
        'trainer' => 'Trainer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ParticipantAttachment::class);
    }

    public function mobilities(): BelongsToMany
    {
        return $this->belongsToMany(ProjectMobility::class, 'mobility_participant')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Participant $participant): void {
            $participant->complete_name = trim((string) ($participant->complete_name ?: trim($participant->first_name.' '.$participant->last_name)));

            if ($participant->complete_name !== '' && (blank($participant->first_name) || blank($participant->last_name))) {
                [$participant->first_name, $participant->last_name] = self::splitCompleteName($participant->complete_name);
            }
        });

        static::deleting(function (Participant $participant): void {
            $participant->attachments()->get()->each->delete();
        });
    }

    public function fullName(): string
    {
        return trim((string) ($this->complete_name ?: trim($this->first_name.' '.$this->last_name)));
    }

    public static function splitCompleteName(string $completeName): array
    {
        $name = preg_replace('/\s+/', ' ', trim($completeName));

        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name);

        if (count($parts) === 1) {
            return [$name, ''];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), $lastName];
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Data de referinta pentru calculul varstei:
     * data inceperii mobilitatii daca exista, altfel data curenta.
     */
    public function referenceDate(): Carbon
    {
        return $this->project?->mobility_start_date
            ? Carbon::parse($this->project->mobility_start_date)
            : Carbon::now();
    }

    /** Varsta la data de referinta. */
    public function ageAtReference(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return (int) Carbon::parse($this->birth_date)->diffInYears($this->referenceDate());
    }

    /**
     * Minor = sub 18 ani la data de referinta (inceputul mobilitatii sau azi).
     * Minorii au nevoie de acord parental.
     */
    public function isMinor(): bool
    {
        $age = $this->ageAtReference();

        return $age !== null && $age < 18;
    }

    /**
     * Tipurile de documente necesare pentru acest participant.
     * GDPR + agreement intotdeauna; acord parental doar pentru minori.
     */
    public function requiredDocTypes(): array
    {
        $required = ['gdpr', 'agreement'];
        if ($this->isMinor()) {
            $required[] = 'parental';
        }

        return $required;
    }

    /** Tipurile de documente necesare care LIPSESC. */
    public function missingDocTypes(): array
    {
        $have = $this->attachments->pluck('type')->all();

        return array_values(array_diff($this->requiredDocTypes(), $have));
    }

    /** Are toate documentele necesare? */
    public function hasCompleteDocs(): bool
    {
        return count($this->missingDocTypes()) === 0;
    }
}
