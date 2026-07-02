<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Participant;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Services\ProjectDocumentChecklist;
use App\Services\ProjectReadinessCheck;
use App\Services\TaskNotificationService;
use App\Support\AuthorizesProjectManagement;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewProjectOverview extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-overview';

    public bool $showTaskModal = false;

    public ?int $editingTaskId = null;

    public string $taskTitle = '';

    public string $taskDescription = '';

    public ?string $taskDueDate = null;

    public ?int $taskAssignedTo = null;

    public string $taskPriority = 'normal';

    public string $taskFilter = 'open';

    public bool $showTransitionReadinessModal = false;

    public ?string $pendingTransitionTarget = null;

    public array $pendingTransitionIssues = [];

    public array $pendingTransitionSummary = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageAccess')
                ->label('Project access')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->modalHeading('Control project access')
                ->modalDescription('Workspace owners and admins always retain access. Choose whether other collaborators see this project.')
                ->fillForm(fn (): array => [
                    'access_mode' => $this->record->access_mode ?: 'workspace',
                    'member_ids' => $this->record->members()->pluck('users.id')->all(),
                ])
                ->form([
                    Select::make('access_mode')
                        ->label('Visibility')
                        ->options([
                            'workspace' => 'Everyone in this workspace',
                            'restricted' => 'Only selected collaborators',
                        ])
                        ->required()
                        ->native(false),
                    Select::make('member_ids')
                        ->label('Selected collaborators')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => $this->record->workspace->users()
                            ->wherePivotIn('role', ['member', 'viewer'])
                            ->orderBy('name')
                            ->pluck('name', 'users.id')
                            ->all())
                        ->helperText('Owners and admins are included automatically.'),
                ])
                ->action(function (array $data): void {
                    abort_unless($this->record->workspace->canManageMembersBy(auth()->user()), 403);
                    abort_unless(in_array($data['access_mode'], ['workspace', 'restricted'], true), 422);
                    $allowedIds = $this->record->workspace->users()
                        ->wherePivotIn('role', ['member', 'viewer'])
                        ->whereKey($data['member_ids'] ?? [])
                        ->pluck('users.id')
                        ->all();
                    $this->record->update(['access_mode' => $data['access_mode']]);
                    $this->record->members()->sync($data['access_mode'] === 'restricted' ? $allowedIds : []);
                    Notification::make()->title('Project access updated')->success()->send();
                })
                ->visible(fn (): bool => $this->record->workspace->canManageMembersBy(auth()->user())),
            Action::make('addTask')
                ->label('Add task')
                ->icon('heroicon-o-plus')
                ->action(fn () => $this->openTaskCreate())
                ->visible(fn (): bool => $this->record->canBeManagedBy(auth()->user())),
            Action::make('archiveProject')
                ->label('Archive project')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Archive '.$this->record->name.'?')
                ->modalDescription('The project will leave active views, dashboards, tasks and reminders. Its data and files remain available for restoration.')
                ->action(function (): void {
                    abort_unless($this->record->canBeManagedBy(auth()->user()), 403);
                    $this->record->delete();
                    Notification::make()->title('Project archived')->success()->send();
                    $this->redirect(ProjectResource::getUrl('index', ['archived' => true]));
                })
                ->visible(fn (): bool => $this->record->canBeManagedBy(auth()->user())),
        ];
    }

    public function getStatusEnum(): ProjectStatus
    {
        return $this->record->statusEnum();
    }

    public function getSectionCount(): int
    {
        return ProjectApplicationSection::where('project_id', $this->record->id)->count();
    }

    public function getApplicationSummary(): array
    {
        $sections = ProjectApplicationSection::query()
            ->where('project_id', $this->record->id)
            ->get();
        $completed = $sections->filter(fn (ProjectApplicationSection $section): bool => filled(trim(strip_tags($section->content ?? ''))))->count();
        $total = $sections->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    public function getParticipantSummary(): array
    {
        $participants = Participant::query()
            ->where('project_id', $this->record->id)
            ->with('attachments')
            ->get();
        $complete = $participants->filter->hasCompleteDocs()->count();

        return [
            'total' => $participants->count(),
            'complete' => $complete,
            'incomplete' => $participants->count() - $complete,
        ];
    }

    public function getDocumentSummary(): array
    {
        if (! $this->record->isManagementStage()) {
            return [
                'complete' => 0,
                'issues' => 0,
                'files' => $this->record->documents()->count(),
                'checklist_applies' => false,
            ];
        }

        $checklist = app(ProjectDocumentChecklist::class)->build($this->record);

        return [
            'complete' => $checklist['complete'],
            'issues' => $checklist['attention'] + $checklist['missing'],
            'files' => $this->record->documents()->count(),
            'checklist_applies' => true,
        ];
    }

    public function getProjectReadiness(): array
    {
        return app(ProjectReadinessCheck::class)->build($this->record);
    }

    public function getRecentActivity()
    {
        return $this->record->activityLogs()
            ->with('user')
            ->latest()
            ->limit(12)
            ->get();
    }

    public function getProjectTasks()
    {
        return $this->record->tasks()
            ->with('assignee')
            ->when($this->taskFilter === 'open', fn ($query) => $query->where('status', 'open'))
            ->when($this->taskFilter === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest('id')
            ->get();
    }

    public function getTaskAssignees()
    {
        $query = $this->record->workspace->users()->orderBy('name');

        if ($this->record->access_mode === 'restricted') {
            $allowedIds = $this->record->workspace->users()
                ->wherePivotIn('role', ['owner', 'admin'])
                ->pluck('users.id')
                ->merge($this->record->members()->pluck('users.id'))
                ->unique();
            $query->whereKey($allowedIds);
        }

        return $query->get();
    }

    public function openTaskCreate(): void
    {
        $this->authorizeProjectManagement();
        $this->resetTaskForm();
        $this->showTaskModal = true;
    }

    public function openTaskEdit(int $taskId): void
    {
        $this->authorizeProjectManagement();
        $task = $this->record->tasks()->findOrFail($taskId);
        $this->editingTaskId = $task->id;
        $this->taskTitle = $task->title;
        $this->taskDescription = $task->description ?? '';
        $this->taskDueDate = $task->due_date?->format('Y-m-d');
        $this->taskAssignedTo = $task->assigned_to;
        $this->taskPriority = $task->priority;
        $this->resetErrorBag();
        $this->showTaskModal = true;
    }

    public function saveTask(TaskNotificationService $notifications): void
    {
        $this->authorizeProjectManagement();
        $data = $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:2000'],
            'taskDueDate' => ['nullable', 'date'],
            'taskAssignedTo' => ['nullable', 'integer'],
            'taskPriority' => ['required', 'in:low,normal,high'],
        ]);

        if ($data['taskAssignedTo'] && ! $this->record->canBeAccessedBy(User::find($data['taskAssignedTo']))) {
            $this->addError('taskAssignedTo', 'Choose a collaborator with access to this project.');

            return;
        }

        $attributes = [
            'title' => trim($data['taskTitle']),
            'description' => filled($data['taskDescription']) ? trim($data['taskDescription']) : null,
            'due_date' => $data['taskDueDate'],
            'assigned_to' => $data['taskAssignedTo'],
            'priority' => $data['taskPriority'],
        ];

        $wasEditing = $this->editingTaskId !== null;
        if ($wasEditing) {
            $task = $this->record->tasks()->findOrFail($this->editingTaskId);
            $previousAssignee = $task->assigned_to;
            $previousDueDate = $task->due_date?->format('Y-m-d');
            if ($previousDueDate !== $data['taskDueDate'] || $previousAssignee !== $data['taskAssignedTo']) {
                $attributes['reminder_sent_at'] = null;
                $attributes['overdue_notified_at'] = null;
            }
            $task->update($attributes);

            if ($task->status === 'open' && $task->assigned_to && $previousAssignee !== $task->assigned_to) {
                $notifications->sendAssignment($task);
            }
        } else {
            $task = $this->record->tasks()->create([
                ...$attributes,
                'created_by' => auth()->id(),
            ]);

            if ($task->assigned_to) {
                $notifications->sendAssignment($task);
            }
        }

        $this->showTaskModal = false;
        $this->resetTaskForm();
        Notification::make()->title($wasEditing ? 'Task updated' : 'Task added')->success()->send();
    }

    public function toggleTask(int $taskId): void
    {
        $task = $this->record->tasks()->findOrFail($taskId);
        abort_unless($task->canBeCompletedBy(auth()->user()), 403);
        $completed = ! $task->isCompleted();
        $task->update([
            'status' => $completed ? 'completed' : 'open',
            'completed_at' => $completed ? now() : null,
            'completed_by' => $completed ? auth()->id() : null,
            'reminder_sent_at' => $completed ? $task->reminder_sent_at : null,
            'overdue_notified_at' => $completed ? $task->overdue_notified_at : null,
        ]);
    }

    public function createTasksFromReadiness(): void
    {
        $this->authorizeProjectManagement();

        $readiness = $this->getProjectReadiness();
        $issues = collect($readiness['items'])
            ->filter(fn (array $item): bool => in_array($item['status'], ['missing', 'attention'], true))
            ->reject(fn (array $item): bool => $item['target'] === 'tasks')
            ->take(8);

        $created = 0;

        foreach ($issues as $item) {
            $title = 'Resolve: '.$item['label'];
            $alreadyExists = $this->record->tasks()
                ->where('status', 'open')
                ->where('title', $title)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $this->record->tasks()->create([
                'title' => $title,
                'description' => $item['detail']."\n\nGenerated from Project readiness check.",
                'priority' => $item['severity'] === 'critical' ? 'high' : 'normal',
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            $created++;
        }

        Notification::make()
            ->title($created > 0 ? "{$created} readiness task(s) created" : 'No new readiness tasks')
            ->body($created > 0 ? 'Open tasks now contains the most important readiness issues.' : 'Matching open tasks already exist for the current readiness issues.')
            ->success()
            ->send();
    }

    public function deleteTask(int $taskId): void
    {
        $this->authorizeProjectManagement();
        $this->record->tasks()->findOrFail($taskId)->delete();
        Notification::make()->title('Task deleted')->success()->send();
    }

    private function resetTaskForm(): void
    {
        $this->editingTaskId = null;
        $this->taskTitle = '';
        $this->taskDescription = '';
        $this->taskDueDate = null;
        $this->taskAssignedTo = null;
        $this->taskPriority = 'normal';
        $this->resetErrorBag();
    }

    public function getModuleUrls(): array
    {
        return [
            'application' => ProjectResource::getUrl('write', ['record' => $this->record]),
            'budget' => ProjectResource::getUrl($this->record->isWritingStage() ? 'estimate' : 'board', ['record' => $this->record]),
            'participants' => ProjectResource::getUrl('participants', ['record' => $this->record]),
            'documents' => ProjectResource::getUrl('documents', ['record' => $this->record]),
            'settings' => ProjectResource::getUrl('edit', ['record' => $this->record]),
        ];
    }

    public function getNextStep(): array
    {
        $urls = $this->getModuleUrls();

        return match ($this->record->statusEnum()) {
            ProjectStatus::Writing => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Continue writing the application',
                'description' => 'Complete the application sections and confirm the grant estimate before submission.',
                'label' => 'Open application',
                'url' => $urls['application'],
                'icon' => 'heroicon-o-pencil-square',
            ],
            ProjectStatus::Submitted => [
                'eyebrow' => 'Current position',
                'title' => 'Awaiting the funding decision',
                'description' => 'Keep the submitted version unchanged. Record the result using the status actions when it arrives.',
                'label' => null,
                'url' => null,
                'icon' => 'heroicon-o-clock',
            ],
            ProjectStatus::Rejected, ProjectStatus::Revise => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Prepare the application revision',
                'description' => 'Review the feedback and update the application before submitting it again.',
                'label' => 'Open application',
                'url' => $urls['application'],
                'icon' => 'heroicon-o-arrow-path',
            ],
            ProjectStatus::Approved => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Review the approved budget',
                'description' => 'Confirm the grant and budget baskets before starting project implementation.',
                'label' => 'Open budget',
                'url' => $urls['budget'],
                'icon' => 'heroicon-o-banknotes',
            ],
            ProjectStatus::Active => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Keep implementation records current',
                'description' => 'Review participant files, expenses and signed project documents as delivery progresses.',
                'label' => 'Review participants',
                'url' => $urls['participants'],
                'icon' => 'heroicon-o-clipboard-document-check',
            ],
            ProjectStatus::Completed => [
                'eyebrow' => 'Recommended next step',
                'title' => 'Complete the final project file',
                'description' => 'Resolve remaining checklist items and keep the signed records together.',
                'label' => 'Review documents',
                'url' => $urls['documents'],
                'icon' => 'heroicon-o-archive-box',
            ],
        };
    }

    public function requestTransitionTo(string $target): void
    {
        $this->authorizeProjectManagement();

        $targetEnum = ProjectStatus::tryFrom($target);
        if (! $targetEnum) {
            return;
        }

        $current = $this->record->statusEnum();

        if (! $current->canTransitionTo($targetEnum)) {
            Notification::make()
                ->title('That status change is not allowed from '.$current->getLabel())
                ->danger()
                ->send();

            return;
        }

        $readiness = $this->getProjectReadiness();
        $issues = collect($readiness['items'])
            ->filter(fn (array $item): bool => in_array($item['status'], ['missing', 'attention'], true))
            ->take(7)
            ->values();

        if ($issues->isEmpty()) {
            $this->transitionTo($target);

            return;
        }

        $this->pendingTransitionTarget = $target;
        $this->pendingTransitionIssues = $issues->all();
        $this->pendingTransitionSummary = [
            'score' => $readiness['score'],
            'status' => $readiness['status'],
            'critical' => $readiness['critical'],
            'warning' => $readiness['warning'],
            'target_label' => $targetEnum->getLabel(),
            'current_label' => $current->getLabel(),
        ];
        $this->showTransitionReadinessModal = true;
    }

    public function confirmPendingTransition(): void
    {
        $target = $this->pendingTransitionTarget;
        $this->closeTransitionReadinessModal();

        if ($target) {
            $this->transitionTo($target);
        }
    }

    public function closeTransitionReadinessModal(): void
    {
        $this->showTransitionReadinessModal = false;
        $this->pendingTransitionTarget = null;
        $this->pendingTransitionIssues = [];
        $this->pendingTransitionSummary = [];
    }

    public function transitionTo(string $target): void
    {
        $this->authorizeProjectManagement();
        $targetEnum = ProjectStatus::tryFrom($target);
        if (! $targetEnum) {
            return;
        }

        $current = $this->record->statusEnum();

        if (! $current->canTransitionTo($targetEnum)) {
            Notification::make()
                ->title('That status change is not allowed from '.$current->getLabel())
                ->danger()
                ->send();

            return;
        }

        $this->record->status = $targetEnum->value;
        $this->record->save();

        // The magic moment: approval pre-fills the grant and seeds the baskets
        // from the budget estimate (only where not already set, so manual edits
        // are never overwritten).
        if ($targetEnum === ProjectStatus::Approved) {
            $this->applyEstimateToBudget();
        }

        $this->record->refresh();

        Notification::make()
            ->title('Status updated to '.$targetEnum->getLabel())
            ->success()
            ->send();
    }

    protected function applyEstimateToBudget(): void
    {
        $estimate = $this->record->action_data['estimate'] ?? null;
        if (! is_array($estimate)) {
            return;
        }

        $lines = $estimate['lines'] ?? [];
        $total = (float) ($estimate['total'] ?? 0);

        if ($total <= 0) {
            return;
        }

        // Pre-fill grant figures only when still empty.
        $dirty = false;
        if ((float) $this->record->approved_budget <= 0) {
            $this->record->approved_budget = $total;
            $dirty = true;
        }
        if ((float) $this->record->total_budget <= 0) {
            $this->record->total_budget = $total;
            $dirty = true;
        }
        if ($dirty) {
            $this->record->save();
        }

        // Map estimate lines onto the default baskets by title.
        $map = [
            'Travel' => (float) ($lines['travel'] ?? 0),
            'Individual Support' => (float) ($lines['is'] ?? 0),
            'Organisational Support' => (float) ($lines['os'] ?? 0),
            'Inclusion Support' => (float) ($lines['inclusion'] ?? 0),
        ];

        foreach ($this->record->budgetLines as $line) {
            if (array_key_exists($line->title, $map) && (float) $line->allocated_budget <= 0) {
                $line->allocated_budget = $map[$line->title];
                $line->save();
            }
        }

        Notification::make()
            ->title('Grant and baskets pre-filled from your estimate')
            ->body('Review them in the Budget board; adjust the confirmed grant in Settings if needed.')
            ->success()
            ->send();
    }
}
