<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /** The earliest relevant date, kept for numeric exports and legacy callers. */
    public function referenceDate(): Carbon
    {
        return $this->referenceDates()->first();
    }

    /** Age at the earliest relevant date. */
    public function ageAtReference(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return $this->ageAt($this->referenceDate());
    }

    /**
     * A participant is a minor when they are under 18 at the start of at least
     * one assigned mobility. This intentionally covers people who turn 18
     * between two mobilities in the same project.
     */
    public function isMinor(): bool
    {
        if (! $this->birth_date) {
            return false;
        }

        return $this->referenceDates()
            ->contains(fn (Carbon $date): bool => $this->ageAt($date) < 18);
    }

    public function isMinorForMobility(ProjectMobility $mobility): bool
    {
        if (! $this->birth_date || ! $mobility->start_date) {
            return false;
        }

        return $this->ageAt(Carbon::parse($mobility->start_date)) < 18;
    }

    /**
     * Human-friendly age for the participant register. A range is displayed
     * when the person has a birthday between assigned mobilities.
     */
    public function ageDisplay(): ?string
    {
        if (! $this->birth_date) {
            return null;
        }

        $ages = $this->referenceDates()
            ->map(fn (Carbon $date): int => $this->ageAt($date))
            ->unique()
            ->sort()
            ->values();

        if ($ages->count() < 2) {
            return (string) $ages->first();
        }

        return $ages->first().'–'.$ages->last();
    }

    /** @return array<int, string> */
    public function minorMobilityNames(): array
    {
        return $this->assignedMobilities()
            ->filter(fn (ProjectMobility $mobility): bool => $this->isMinorForMobility($mobility))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Assigned mobility dates are authoritative. An unassigned participant
     * falls back to the project period, then today, without borrowing dates
     * from an unrelated mobility.
     *
     * @return Collection<int, Carbon>
     */
    private function referenceDates(): Collection
    {
        $dates = $this->assignedMobilities()
            ->pluck('start_date')
            ->filter()
            ->map(fn ($date): Carbon => Carbon::parse($date))
            ->sortBy(fn (Carbon $date): string => $date->toDateString())
            ->values();

        if ($dates->isNotEmpty()) {
            return $dates;
        }

        $project = $this->relationLoaded('project') ? $this->project : $this->project()->first();

        return collect([
            $project?->start_date ? Carbon::parse($project->start_date) : Carbon::now(),
        ]);
    }

    /** @return Collection<int, ProjectMobility> */
    private function assignedMobilities(): Collection
    {
        return $this->relationLoaded('mobilities')
            ? $this->mobilities
            : $this->mobilities()->get();
    }

    private function ageAt(Carbon $date): int
    {
        return (int) Carbon::parse($this->birth_date)->diffInYears($date);
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
