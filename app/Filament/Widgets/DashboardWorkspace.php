<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
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
        $projects = Project::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Rejected->value])
            ->with(['budgetLines.expenses', 'participants.attachments', 'documents'])
            ->latest('updated_at')
            ->get();

        $currentProjects = $projects
            ->sortBy(fn (Project $project): array => [
                $this->statusPriority($project),
                $project->mobility_start_date?->timestamp ?? $project->start_date?->timestamp ?? PHP_INT_MAX,
            ])
            ->take(4)
            ->values();

        $attention = $this->attentionItems($projects);
        $milestones = $this->milestones($projects);
        $primaryProject = $projects->first(fn (Project $project) => $project->isManagementStage());
        $writingProject = $projects->first(fn (Project $project) => $project->isWritingStage());
        $canManage = Filament::getTenant()?->canBeManagedBy(auth()->user()) ?? false;

        return [
            'projects' => $currentProjects,
            'projectCount' => $projects->count(),
            'attention' => $attention->take(6),
            'attentionCount' => $attention->count(),
            'milestones' => $milestones->take(6),
            'milestoneCount' => $milestones->count(),
            'quickActions' => $this->quickActions($primaryProject, $writingProject, $canManage),
            'projectsUrl' => ProjectResource::getUrl('index'),
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
    private function attentionItems(Collection $projects): Collection
    {
        $items = collect();
        $today = today();

        foreach ($projects as $project) {
            $status = $project->statusEnum();

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

            if ($project->mobility_start_date) {
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
                        'url' => ProjectResource::getUrl('overview', ['record' => $project]),
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
            'url' => ProjectResource::getUrl($page, ['record' => $project]),
        ];
    }

    private function relativeDays(int $days): string
    {
        return match ($days) {
            0 => 'today',
            1 => 'tomorrow',
            default => 'in '.$days.' days',
        };
    }

    private function quickActions(?Project $primaryProject, ?Project $writingProject, bool $canManage): array
    {
        $fallback = ProjectResource::getUrl('index');
        $actions = [];

        if ($canManage) {
            $actions[] = [
                'label' => 'New project',
                'description' => 'Start an application',
                'icon' => 'heroicon-o-plus',
                'url' => ProjectResource::getUrl('create'),
            ];
        }

        if ($writingProject) {
            $actions[] = [
                'label' => 'Continue application',
                'description' => $writingProject->name,
                'icon' => 'heroicon-o-pencil-square',
                'url' => ProjectResource::getUrl('write', ['record' => $writingProject]),
            ];
        }

        $actions[] = [
            'label' => $canManage ? 'Manage expenses' : 'View budget',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-banknotes',
            'url' => $primaryProject ? ProjectResource::getUrl('board', ['record' => $primaryProject]) : $fallback,
        ];

        $actions[] = [
            'label' => $canManage ? 'Add participants' : 'View participants',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-users',
            'url' => $primaryProject ? ProjectResource::getUrl('participants', ['record' => $primaryProject]) : $fallback,
        ];

        $actions[] = [
            'label' => $canManage ? 'Create documents' : 'View documents',
            'description' => $primaryProject?->name ?? 'Choose a project',
            'icon' => 'heroicon-o-document-duplicate',
            'url' => $primaryProject ? ProjectResource::getUrl('documents', ['record' => $primaryProject]) : $fallback,
        ];

        return array_slice($actions, 0, 4);
    }
}
