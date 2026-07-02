<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use Illuminate\Support\Collection;

class ProjectReadinessCheck
{
    public function __construct(
        protected ProjectDocumentChecklist $documents,
    ) {}

    public function build(Project $project): array
    {
        $project->loadMissing([
            'applicationSections',
            'participants.attachments',
            'documents',
            'budgetLines.expenses',
            'tasks',
        ]);

        $status = $project->statusEnum();
        $items = collect()
            ->merge($this->planningItems($project))
            ->merge($this->applicationItems($project))
            ->merge($this->budgetItems($project))
            ->merge($this->participantItems($project))
            ->merge($this->documentItems($project, $status))
            ->merge($this->taskItems($project));

        $required = $items->reject(fn (array $item): bool => ($item['status'] ?? null) === 'optional');
        $complete = $required->where('status', 'complete')->count();
        $score = $required->isEmpty() ? 100 : (int) round($complete / $required->count() * 100);
        $critical = $items->where('severity', 'critical')->whereIn('status', ['missing', 'attention'])->count();
        $warning = $items->where('severity', 'warning')->whereIn('status', ['missing', 'attention'])->count();
        $attention = $items->where('status', 'attention')->count();
        $missing = $items->where('status', 'missing')->count();

        $next = $items->first(fn (array $item): bool => in_array($item['status'], ['missing', 'attention'], true))
            ?? $items->first();

        return [
            'score' => $score,
            'status' => $this->statusLabel($score, $critical, $warning),
            'tone' => $this->tone($score, $critical, $warning),
            'complete' => $items->where('status', 'complete')->count(),
            'attention' => $attention,
            'missing' => $missing,
            'optional' => $items->where('status', 'optional')->count(),
            'critical' => $critical,
            'warning' => $warning,
            'next' => $next,
            'items' => $items->values()->all(),
            'groups' => $items->groupBy('group')->map(fn (Collection $groupItems, string $group): array => [
                'label' => $group,
                'score' => $this->groupScore($groupItems),
                'complete' => $groupItems->where('status', 'complete')->count(),
                'issues' => $groupItems->whereIn('status', ['missing', 'attention'])->count(),
                'items' => $groupItems->values()->all(),
            ])->values()->all(),
        ];
    }

    protected function planningItems(Project $project): array
    {
        return [
            $this->item(
                'Project dates',
                $project->start_date && $project->end_date ? 'complete' : 'missing',
                $project->start_date && $project->end_date
                    ? $project->start_date->format('d M Y').' - '.$project->end_date->format('d M Y')
                    : 'Add start and end dates in Project settings.',
                'Planning',
                'settings',
                'critical',
            ),
            $this->item(
                'Mobility dates',
                $project->mobility_start_date && $project->mobility_end_date ? 'complete' : 'attention',
                $project->mobility_start_date && $project->mobility_end_date
                    ? $project->mobility_start_date->format('d M Y').' - '.$project->mobility_end_date->format('d M Y')
                    : 'Add mobility dates when the activity period is known.',
                'Planning',
                'settings',
                'warning',
            ),
            $this->item(
                'Application template',
                filled($project->ka_action) ? 'complete' : 'optional',
                filled($project->ka_action)
                    ? strtoupper((string) $project->ka_action).' selected'
                    : 'No application template selected; this project can still be managed manually.',
                'Planning',
                'settings',
                'warning',
            ),
        ];
    }

    protected function applicationItems(Project $project): array
    {
        $sections = $project->applicationSections;
        $usesWritingTemplate = filled($project->ka_action) || $sections->isNotEmpty();
        $answered = $sections->filter(fn (ProjectApplicationSection $section): bool => filled(trim(strip_tags($section->content ?? ''))))->count();
        $ready = $sections->where('review_status', 'ready')->count();
        $overLimit = $sections->filter(fn (ProjectApplicationSection $section): bool => $section->char_limit
            && mb_strlen(strip_tags((string) $section->content)) > $section->char_limit)->count();

        return [
            $this->item(
                'Application template',
                $sections->isNotEmpty() ? 'complete' : ($usesWritingTemplate ? 'missing' : 'optional'),
                $sections->isNotEmpty()
                    ? $sections->count().' writing questions loaded'
                    : ($usesWritingTemplate ? 'Load the official application template.' : 'No Writing template is required for manual project management.'),
                'Application',
                'application',
                'critical',
            ),
            $this->item(
                'Application answers',
                $sections->isEmpty() ? ($usesWritingTemplate ? 'missing' : 'optional') : ($answered === $sections->count() ? 'complete' : 'attention'),
                $sections->isEmpty()
                    ? ($usesWritingTemplate ? 'No writing questions loaded' : 'Writing workspace not started.')
                    : $answered.'/'.$sections->count().' sections contain an answer',
                'Application',
                'application',
                'critical',
            ),
            $this->item(
                'Review status',
                $sections->isEmpty() ? 'optional' : ($ready === $sections->count() ? 'complete' : 'attention'),
                $sections->isEmpty() ? 'No sections to review yet' : $ready.'/'.$sections->count().' sections marked ready',
                'Application',
                'application',
                'warning',
            ),
            $this->item(
                'Character limits',
                $sections->isEmpty() ? 'optional' : ($overLimit === 0 ? 'complete' : 'attention'),
                $sections->isEmpty()
                    ? 'No configured writing sections.'
                    : ($overLimit === 0 ? 'No answers exceed their configured limit' : $overLimit.' answer(s) exceed the limit'),
                'Application',
                'application',
                'warning',
            ),
        ];
    }

    protected function budgetItems(Project $project): array
    {
        $effectiveBudget = (float) $project->effective_budget;
        $allocated = (float) $project->budgetLines->sum('allocated_budget');
        $expenses = $project->budgetLines->flatMap->expenses;
        $missingEvidence = $expenses->filter(fn (Expense $expense): bool => ! $expense->attachmentExists())->count();
        $overspent = $project->budgetLines->filter(fn ($line): bool => $line->remaining < -0.01)->count();

        return [
            $this->item(
                'Grant amount',
                $effectiveBudget > 0 ? 'complete' : 'missing',
                $effectiveBudget > 0 ? number_format($effectiveBudget, 2).' EUR configured' : 'Add a requested or approved grant amount.',
                'Budget',
                $project->isWritingStage() ? 'budget' : 'budget',
                'critical',
            ),
            $this->item(
                'Budget baskets',
                $project->isWritingStage()
                    ? 'optional'
                    : ($allocated > 0 ? 'complete' : 'attention'),
                $project->isWritingStage()
                    ? 'Detailed baskets become required after approval.'
                    : ($allocated > 0 ? number_format($allocated, 2).' EUR allocated to baskets' : 'Allocate the approved grant to budget baskets.'),
                'Budget',
                'budget',
                'warning',
            ),
            $this->item(
                'Expense evidence',
                $expenses->isEmpty()
                    ? ($project->isManagementStage() ? 'attention' : 'optional')
                    : ($missingEvidence === 0 ? 'complete' : 'attention'),
                $expenses->isEmpty()
                    ? ($project->isManagementStage() ? 'No expenses recorded yet.' : 'Expenses are checked during implementation.')
                    : ($missingEvidence === 0 ? $expenses->count().' expenses have evidence' : $missingEvidence.'/'.$expenses->count().' expenses miss evidence'),
                'Budget',
                'budget',
                'warning',
            ),
            $this->item(
                'Overspending',
                $overspent === 0 ? 'complete' : 'attention',
                $overspent === 0 ? 'No budget basket is overspent' : $overspent.' budget basket(s) are overspent',
                'Budget',
                'budget',
                'critical',
            ),
        ];
    }

    protected function participantItems(Project $project): array
    {
        $participants = $project->participants;
        $completeDocs = $participants->filter(fn (Participant $participant): bool => $participant->hasCompleteDocs())->count();
        $missingContact = $participants->filter(fn (Participant $participant): bool => blank($participant->email) || blank($participant->phone))->count();
        $missingBirthDate = $participants->filter(fn (Participant $participant): bool => blank($participant->birth_date))->count();

        return [
            $this->item(
                'Participant register',
                $participants->isNotEmpty() ? 'complete' : 'attention',
                $participants->isNotEmpty() ? $participants->count().' participant(s) added' : 'Add participants when the group is known.',
                'Participants',
                'participants',
                'warning',
            ),
            $this->item(
                'Participant documents',
                $participants->isEmpty()
                    ? 'optional'
                    : ($completeDocs === $participants->count() ? 'complete' : 'attention'),
                $participants->isEmpty() ? 'No participant records yet' : $completeDocs.'/'.$participants->count().' participants have required files',
                'Participants',
                'participants',
                'warning',
            ),
            $this->item(
                'Participant contact data',
                $participants->isEmpty()
                    ? 'optional'
                    : ($missingContact === 0 && $missingBirthDate === 0 ? 'complete' : 'attention'),
                $participants->isEmpty()
                    ? 'No participant records yet'
                    : ($missingContact === 0 && $missingBirthDate === 0 ? 'Core contact data complete' : $missingContact.' missing contact data, '.$missingBirthDate.' missing birth date'),
                'Participants',
                'participants',
                'warning',
            ),
        ];
    }

    protected function documentItems(Project $project, ProjectStatus $status): array
    {
        $checklist = $this->documents->build($project);
        $requiredTotal = max(1, count($checklist['items']) - $checklist['optional']);
        $documentReadiness = (int) round($checklist['complete'] / $requiredTotal * 100);
        $awaitingSignature = $project->documents
            ->whereIn('type', [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT])
            ->filter(fn (ProjectDocument $document): bool => ! $document->hasSignedCopy())
            ->count();

        $documentStatus = $status->isWritingStage()
            ? 'optional'
            : ($checklist['missing'] > 0 ? 'missing' : ($checklist['attention'] > 0 ? 'attention' : 'complete'));

        return [
            $this->item(
                'Project file checklist',
                $documentStatus,
                $status->isWritingStage()
                    ? 'Project file becomes required after approval.'
                    : $documentReadiness.'% document checklist readiness',
                'Documents',
                'documents',
                'critical',
            ),
            $this->item(
                'Signed generated records',
                $awaitingSignature === 0 ? 'complete' : ($status->isWritingStage() ? 'optional' : 'attention'),
                $awaitingSignature === 0 ? 'No generated records await signature' : $awaitingSignature.' generated record(s) await signed copies',
                'Documents',
                'documents',
                'warning',
            ),
        ];
    }

    protected function taskItems(Project $project): array
    {
        $open = $project->tasks->where('status', 'open')->count();
        $overdue = $project->tasks->filter(fn (ProjectTask $task): bool => $task->isOverdue())->count();

        return [
            $this->item(
                'Open tasks',
                $overdue > 0 ? 'attention' : 'complete',
                $overdue > 0 ? $overdue.' overdue task(s), '.$open.' open total' : $open.' open task(s)',
                'Tasks',
                'tasks',
                'warning',
            ),
        ];
    }

    protected function item(string $label, string $status, string $detail, string $group, string $target, string $severity = 'warning'): array
    {
        return compact('label', 'status', 'detail', 'group', 'target', 'severity');
    }

    protected function statusLabel(int $score, int $critical, int $warning): string
    {
        if ($critical > 0) {
            return 'Critical items need attention';
        }

        if ($score >= 90 && $warning === 0) {
            return 'Project looks ready';
        }

        if ($score >= 70) {
            return 'Almost ready';
        }

        return 'Needs work before it is ready';
    }

    protected function tone(int $score, int $critical, int $warning): string
    {
        if ($critical > 0) {
            return 'danger';
        }

        if ($score >= 90 && $warning === 0) {
            return 'success';
        }

        if ($score >= 70) {
            return 'warning';
        }

        return 'danger';
    }

    protected function groupScore(Collection $items): int
    {
        $required = $items->reject(fn (array $item): bool => $item['status'] === 'optional');

        if ($required->isEmpty()) {
            return 100;
        }

        return (int) round($required->where('status', 'complete')->count() / $required->count() * 100);
    }
}
