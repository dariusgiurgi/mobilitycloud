<?php

namespace App\Observers;

use App\Models\BudgetLine;
use App\Models\BudgetTransfer;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityObserver
{
    public function created(Model $subject): void
    {
        if ($subject instanceof BudgetLine) {
            return;
        }

        $this->record($subject, 'created');
    }

    public function updated(Model $subject): void
    {
        $event = $subject instanceof Project && $subject->wasChanged('status')
            ? 'status_changed'
            : 'updated';

        $this->record($subject, $event);
    }

    public function deleted(Model $subject): void
    {
        $this->record($subject, 'deleted');
    }

    public function restored(Model $subject): void
    {
        $this->record($subject, 'restored');
    }

    private function record(Model $subject, string $event): void
    {
        $project = $this->projectFor($subject);
        if (! $project || ! $project->exists || ($project->isForceDeleting() ?? false)) {
            return;
        }

        $changes = collect(array_keys($subject->getChanges()))
            ->reject(fn (string $field): bool => in_array($field, ['created_at', 'updated_at', 'deleted_at'], true))
            ->values()
            ->all();

        ProjectActivityLog::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $this->description($subject, $event),
            'metadata' => $changes === [] ? null : ['changed_fields' => $changes],
        ]);
    }

    private function projectFor(Model $subject): ?Project
    {
        return match (true) {
            $subject instanceof Project => $subject,
            $subject instanceof Expense => $subject->budgetLine?->project,
            $subject instanceof BudgetLine,
            $subject instanceof BudgetTransfer,
            $subject instanceof Participant,
            $subject instanceof ProjectApplicationSection,
            $subject instanceof ProjectDocument => $subject->project,
            $subject instanceof ProjectTask => $subject->project,
            default => null,
        };
    }

    private function description(Model $subject, string $event): string
    {
        $verb = match ($event) {
            'created' => 'added',
            'deleted' => 'removed',
            'restored' => 'restored',
            'status_changed' => 'changed the project status',
            default => 'updated',
        };

        if ($event === 'status_changed') {
            return $verb.' to '.ucfirst((string) $subject->status);
        }

        return match (true) {
            $subject instanceof Project => match ($event) {
                'created' => 'created the project',
                'deleted' => 'archived the project',
                'restored' => 'restored the project',
                default => $verb.' project settings',
            },
            $subject instanceof Expense => $verb.' expense “'.($subject->description ?: '#'.$subject->id).'”',
            $subject instanceof Participant => $verb.' participant “'.($subject->fullName() ?: '#'.$subject->id).'”',
            $subject instanceof ProjectDocument => $verb.' document “'.($subject->title ?: '#'.$subject->id).'”',
            $subject instanceof ProjectApplicationSection => $verb.' application section “'.$subject->title.'”',
            $subject instanceof BudgetLine => $verb.' budget basket “'.$subject->title.'”',
            $subject instanceof BudgetTransfer => $event === 'created' ? 'recorded a budget transfer' : $verb.' a budget transfer',
            $subject instanceof ProjectTask => $verb.' task “'.$subject->title.'”',
            default => $verb.' project data',
        };
    }
}
