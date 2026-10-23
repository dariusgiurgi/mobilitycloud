<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ParticipantRegister
{
    public function minors(Builder $query, ?Project $project = null): Builder
    {
        return $query->whereNotNull('birth_date')->where(function (Builder $query) use ($project): void {
            $query->whereHas('mobilities', fn (Builder $q) => $this->underEighteen($q->whereNotNull('start_date'), 'project_mobilities.start_date'))
                ->orWhere(function (Builder $q) use ($project): void {
                    $q->whereDoesntHave('mobilities', fn (Builder $m) => $m->whereNotNull('start_date'));
                    if ($project) {
                        $this->underEighteen($q, '?', [($project->start_date ?? today())->toDateString()]);
                    } else {
                        $date = 'COALESCE((SELECT start_date FROM projects WHERE projects.id = participants.project_id), ?)';
                        $this->underEighteen($q, $date, [today()->toDateString()]);
                    }
                });
        });
    }

    private function underEighteen(Builder $query, string $reference, array $bindings = []): Builder
    {
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            // Compare completed birthdays, including leap days, rather than subtracting years from the reference date.
            return $query->whereRaw("(CAST(strftime('%Y', $reference) AS INTEGER) - CAST(strftime('%Y', participants.birth_date) AS INTEGER) - (strftime('%m-%d', $reference) < strftime('%m-%d', participants.birth_date))) < 18", [...$bindings, ...$bindings]);
        }

        return $query->whereRaw("TIMESTAMPDIFF(YEAR, participants.birth_date, $reference) < 18", $bindings);
    }

    public function incomplete(Builder $query, ?Project $project = null): Builder
    {
        return $query->where(function (Builder $query) use ($project): void {
            $query->whereDoesntHave('attachments', fn (Builder $q) => $q->where('type', 'gdpr'))
                ->orWhereDoesntHave('attachments', fn (Builder $q) => $q->where('type', 'agreement'))
                ->orWhere(function (Builder $q) use ($project): void {
                    $this->minors($q, $project)->whereDoesntHave('attachments', fn (Builder $a) => $a->where('type', 'parental'));
                });
        });
    }
}
