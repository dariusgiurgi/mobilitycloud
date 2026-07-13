<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\WorkspaceInvitation;
use App\Services\ProjectReadinessCheck;
use App\Services\ProjectInvitationNotificationService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DashboardWorkspace extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.dashboard-workspace';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    protected function getViewData(): array
    {
        if (auth()->user()) {
            app(ProjectInvitationNotificationService::class)->syncPendingFor(auth()->user());
        }

        $projects = Project::query()
            ->visibleToAccount(auth()->user())
            ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Rejected->value])
            ->with(['ownerAccount', 'members', 'budgetLines.expenses', 'participants.attachments', 'documents', 'tasks.assignee'])
            ->latest('updated_at')
            ->get();

        $currentProjects = $projects
            ->sortBy(fn (Project $project): array => [
                $this->statusPriority($project),
                $project->mobility_start_date?->timestamp ?? $project->start_date?->timestamp ?? PHP_INT_MAX,
            ])
            ->take(4)
            ->values();

        $readiness = $this->readinessItems($projects);
        $attention = $this->attentionItems($projects, $readiness);
        $milestones = $this->milestones($projects);
        $primaryProject = $projects->first(fn (Project $project) => $project->isManagementStage());
        $writingProject = $projects->first(fn (Project $project) => $project->isWritingStage());
        $canManagePrimaryProject = $primaryProject?->canBeManagedBy(auth()->user()) ?? false;
        $canCreate = auth()->user()?->can('create', Project::class) ?? false;
        $pendingInvitations = WorkspaceInvitation::query()
            ->with(['project', 'inviter'])
            ->whereRaw('LOWER(email) = ?', [strtolower((string) auth()->user()?->email)])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->whereHas('project')
            ->latest()
            ->get();

        return [
            'projects' => $currentProjects,
            'projectCount' => $projects->count(),
            'pendingInvitations' => $pendingInvitations,
            'readiness' => $readiness,
            'attention' => $attention->take(6),
            'attentionCount' => $attention->count(),
            'milestones' => $milestones->take(6),
            'milestoneCount' => $milestones->count(),
            'quickActions' => $this->quickActions($primaryProject, $writingProject, $canManagePrimaryProject, $canCreate),
            'projectsUrl' => ProjectResource::accountUrl('index'),
        ];
    }

    private function statusPriority(Project $project): int
    {
        return match ($project->statusEnum()) {
            ProjectStatus::Active => 0,
            ProjectStatus::Approved => 1,
            ProjectStatus::Revise => 2,
            ProjectStatus::Writing => 3,
            ProjectStatus::Submitted => 4,
            default => 5,
        };
    }

    /** @param Collection<int, Project> $projects */
    private function readinessItems(Collection $projects): Collection
    {
        $checker = app(ProjectReadinessCheck::class);

        return $projects
            ->mapWithKeys(fn (Project $project): array => [
                $project->id => $checker->build($project),
            ]);
    }

    /** @param Collection<int, Project> $projects */
    private function attentionItems(Collection $projects, Collection $readiness): Collection
    {
        $items = collect();
        $today = today();

        foreach ($projects as $project) {
            $status = $project->statusEnum();
            $projectReadiness = $readiness->get($project->id);

            if ($projectReadiness && (($projectReadiness['critical'] ?? 0) > 0 || ($projectReadiness['warning'] ?? 0) >= 2)) {
                $next = $projectReadiness['next'] ?? null;
                $items->push([
                    'project' => $project->name,
                    'title' => 'Readiness '.$projectReadiness['score'].'% · '.$projectReadiness['status'],
                    'detail' => $next
                        ? 'Next: '.$next['label'].' · '.$next['detail']
                        : 'Open the readiness panel for the next recommended action.',
                    'severity' => ($projectReadiness['tone'] ?? null) === 'danger' ? 'danger' : 'warning',
                    'url' => $this->readinessUrl($project, $next['target'] ?? null),
                ]);
            }

            if ($status->isManagementStage() && (! $project->start_date || ! $project->end_date)) {
                $items->push($this->attentionItem(
                    $project,
                    'Project dates are incomplete',
                    'Add the implementation start and end dates.',
                    'warning',
                    'edit',
                ));
            }

            if ($status === ProjectStatus::Active && $project->end_date?->isBefore($today)) {
                $items->push($this->attentionItem(
                    $project,
                    'Project end date has passed',
                    'Review the status or complete the project.',
                    'danger',
                    'overview',
                ));
            }

            if ($status->isManagementStage() && $project->mobility_start_date) {
                $days = $today->diffInDays($project->mobility_start_date, false);
                if ($days >= 0 && $days <= 30) {
                    $items->push($this->attentionItem(
                        $project,
                        'Mobility starts '.$this->relativeDays((int) $days),
                        $project->mobility_start_date->format('d M Y'),
                        $days <= 7 ? 'danger' : 'warning',
                        'participants',
                    ));
                }
            }

            if ($status->isManagementStage()) {
                $participantsMissingDocuments = $project->participants
                    ->filter(fn ($participant): bool => ! $participant->hasCompleteDocs())
                    ->count();

                if ($participantsMissingDocuments > 0) {
                    $items->push($this->attentionItem(
                        $project,
                        $participantsMissingDocuments.' participant '.str('record')->plural($participantsMissingDocuments).' incomplete',
                        'Required participant documents are missing.',
                        'warning',
                        'participants',
                    ));
                }

                $expenses = $project->budgetLines->flatMap->expenses;
                $missingEvidence = $expenses->whereNull('attachment_path')->count();

                if ($missingEvidence > 0) {
                    $items->push($this->attentionItem(
                        $project,
                        $missingEvidence.' '.str('expense')->plural($missingEvidence).' without evidence',
                        'Upload the supporting invoice or receipt.',
                        'warning',
                        'board',
                    ));
                }

                $unsignedDocuments = $project->documents
                    ->filter(fn ($document): bool => in_array($document->type, ['attendance', 'expense_report'], true))
                    ->whereNull('signed_path')
                    ->count();

                if ($unsignedDocuments > 0) {
                    $items->push($this->attentionItem(
                        $project,
                        $unsignedDocuments.' generated '.str('document')->plural($unsignedDocuments).' awaiting signature',
                        'Upload the signed copy when available.',
                        'info',
                        'documents',
                    ));
                }
            }

            foreach ($project->tasks->where('status', 'open')->whereNotNull('due_date') as $task) {
                $days = (int) $today->diffInDays($task->due_date, false);
                if ($days < 0) {
                    $items->push($this->attentionItem(
                        $project,
                        'Overdue task: '.$task->title,
                        ($task->assignee?->name ?? 'Unassigned').' · due '.$task->due_date->format('d M Y'),
                        'danger',
                        'overview',
                    ));
                } elseif ($days <= 7) {
                    $items->push($this->attentionItem(
                        $project,
                        'Task due '.$this->relativeDays($days).': '.$task->title,
                        $task->assignee?->name ?? 'Unassigned',
                        $days <= 2 ? 'danger' : 'warning',
                        'overview',
                    ));
                }
            }
        }

        return $items
            ->sortBy(fn (array $item): array => [
                ['danger' => 0, 'warning' => 1, 'info' => 2][$item['severity']] ?? 3,
                $item['project'],
            ])
            ->values();
    }

    /** @param Collection<int, Project> $projects */
    private function milestones(Collection $projects): Collection
    {
        $today = today();
        $until = $today->copy()->addDays(60);
        $fields = [
            'mobility_start_date' => 'Mobility starts',
            'mobility_end_date' => 'Mobility ends',
            'start_date' => 'Project starts',
            'end_date' => 'Project ends',
        ];

        return $projects
            ->flatMap(function (Project $project) use ($fields, $today, $until): array {
                $items = [];

                foreach ($fields as $field => $label) {
                    /** @var Carbon|null $date */
                    $date = $project->{$field};

                    if (! $date || $date->isBefore($today) || $date->isAfter($until)) {
                        continue;
                    }

                    $items[] = [
                        'date' => $date,
                        'label' => $label,
                        'project' => $project->name,
                        'url' => $this->projectUrl($project, 'overview'),
                    ];
                }

                foreach ($project->tasks->where('status', 'open') as $task) {
                    $date = $task->due_date;
                    if (! $date || $date->isBefore($today) || $date->isAfter($until)) {
                        continue;
                    }

                    $items[] = [
                        'date' => $date,
                        'label' => 'Task: '.$task->title,
                        'project' => $project->name,
                        'url' => $this->projectUrl($project, 'overview'),
                    ];
                }

                return $items;
            })
            ->sortBy('date')
            ->values();
    }

    private function attentionItem(
        Project $project,
        string $title,
        string $detail,
        string $severity,
        string $page,
    ): array {
        return [
            'project' => $project->name,
            'title' => $title,
            'detail' => $detail,
            'severity' => $severity,
            'url' => $this->projectUrl($project, $page),
        ];
    }

    private function readinessUrl(Project $project, ?string $target): string
    {
        return match ($target) {
            'application' => $this->projectUrl($project, 'write'),
            'budget' => $this->projectUrl($project, $project->isManagementStage() ? 'board' : 'estimate'),
            'participants' => $this->projectUrl($project, $project->isManagementStage() ? 'participants' : 'overview'),
            'documents' => $this->projectUrl($project, $project->isManagementStage() ? 'documents' : 'overview'),
            'settings' => $this->projectUrl($project, 'edit'),
            default => $this->projectUrl($project, 'overview'),
        };
    }

    private function relativeDays(int $days): string
    {
        return match ($days) {
            0 => 'today',
            1 => 'tomorrow',
            default => 'in '.$days.' days',
        };
    }

    private function quickActions(?Project $primaryProject, ?Project $writingProject, bool $canManage, bool $canCreate): array
    {
        $fallback = ProjectResource::accountUrl('index');
        $actions = [];

        if ($canCreate) {
            $actions[] = [
                'label' => 'New project',
                'description' => 'Start an application',
                'icon' => 'heroicon-o-plus',
                'url' => ProjectResource::accountUrl('create'),
            ];
        }

        if ($writingProject) {
            $actions[] = [
                'label' => 'Continue application',
                'description' => $writingProject->name,
                'icon' => 'heroicon-o-pencil-square',
                'url' => $this->projectUrl($writingProject, 'write'),
            ];
        }

        $actions[] = [
            'label' => $canManage ? 'Manage expenses' : 'View budget',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-banknotes',
            'url' => $primaryProject ? $this->projectUrl($primaryProject, 'board') : $fallback,
        ];

        $actions[] = [
            'label' => $canManage ? 'Add participants' : 'View participants',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-users',
            'url' => $primaryProject ? $this->projectUrl($primaryProject, 'participants') : $fallback,
        ];

        $actions[] = [
            'label' => $canManage ? 'Create documents' : 'View documents',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-document-duplicate',
            'url' => $primaryProject ? $this->projectUrl($primaryProject, 'documents') : $fallback,
        ];

        return array_slice($actions, 0, 4);
    }

    private function projectUrl(Project $project, string $page): string
    {
        return ProjectResource::projectUrl($project, $page);
    }
}
