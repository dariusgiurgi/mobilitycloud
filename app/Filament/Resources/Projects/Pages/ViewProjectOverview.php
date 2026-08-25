<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ProjectInvitationNotification;
use App\Services\ProjectDocumentChecklist;
use App\Services\ProjectInvitationNotificationService;
use App\Services\ProjectReadinessCheck;
use App\Services\StoredFileReplacementService;
use App\Services\TaskNotificationService;
use App\Support\AuthorizesProjectManagement;
use App\Support\StoredFileReference;
use App\Support\StoredFileSwapResult;
use App\Support\UploadedFileSize;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ViewProjectOverview extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;
    use WithFileUploads;

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

    public bool $showApprovalModal = false;

    public $approvedGrantAmount = null;

    public ?string $approvedProjectCode = null;

    public $approvedGrantProof = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        ProjectResource::ensureProjectAccountTenant($this->record);
        $this->authorizeProjectModuleAccess('overview');
        $this->touchProjectCollaboration('overview');
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
                ->modalHeading('Project collaborators')
                ->modalDescription('Choose one simple role for each person. The project owner remains responsible for sharing access and financial decisions.')
                ->fillForm(fn (): array => [
                    'collaborators' => $this->record->members()
                        ->orderBy('name')
                        ->get()
                        ->map(fn (User $user): array => [
                            'user_id' => $user->id,
                            'person' => $user->name.' · '.$user->email,
                            'role' => $user->pivot->role ?: 'editor',
                        ])
                        ->values()
                        ->all(),
                    'invite_email' => '',
                    'invite_role' => 'editor',
                ])
                ->form([
                    Repeater::make('collaborators')
                        ->label('Current project collaborators')
                        ->schema([
                            Hidden::make('user_id'),
                            TextInput::make('person')
                                ->label('Collaborator')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            Radio::make('role')
                                ->label('What can this person do?')
                                ->options(Project::projectRoleOptions())
                                ->descriptions(Project::projectRoleDescriptions())
                                ->columns(3)
                                ->default('editor')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->defaultItems(0)
                        ->addable(false)
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): string => trim(($state['person'] ?? 'Collaborator').' · '.Project::projectRoleLabel($state['role'] ?? null)))
                        ->helperText('Change access here or remove a person. Invitations are sent from the section below.'),
                    TextInput::make('invite_email')
                        ->label('Invite by email')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('collaborator@example.org')
                        ->helperText('They receive an invitation first. Access starts only after they accept it.'),
                    Radio::make('invite_role')
                        ->label('Choose their role')
                        ->options(Project::projectRoleOptions())
                        ->descriptions(Project::projectRoleDescriptions())
                        ->columns(3)
                        ->default('editor')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless($this->record->canManageAccessBy(auth()->user()), 403);

                    $roles = array_keys(Project::projectRoleOptions());
                    $existingMemberIds = $this->record->members()
                        ->pluck('users.id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    $sync = collect($data['collaborators'] ?? [])
                        ->mapWithKeys(function (array $row) use ($existingMemberIds, $roles): array {
                            $userId = (int) ($row['user_id'] ?? 0);
                            $role = in_array($row['role'] ?? null, $roles, true) ? $row['role'] : 'editor';

                            return $userId > 0 && in_array($userId, $existingMemberIds, true)
                                ? [$userId => ['role' => $role]]
                                : [];
                        })
                        ->all();

                    $this->record->update(['access_mode' => 'restricted']);
                    $this->record->members()->sync($sync);

                    $email = Str::lower(trim($data['invite_email'] ?? ''));
                    if ($email !== '') {
                        $this->inviteProjectCollaborator($email, $data['invite_role'] ?? 'editor');
                    }

                    Notification::make()->title('Project access updated')->success()->send();
                })
                ->visible(fn (): bool => $this->record->canManageAccessBy(auth()->user())),
            Action::make('addTask')
                ->label('Add task')
                ->icon('heroicon-o-plus')
                ->action(fn () => $this->openTaskCreate())
                ->visible(fn (): bool => $this->record->canBeCollaboratedOnBy(auth()->user())),
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
            ->with(['attachments', 'mobilities', 'project'])
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
            ->limit(7)
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
        return User::query()
            ->whereIn('id', $this->projectAssigneeIds())
            ->orderBy('name')
            ->get();
    }

    private function projectAssigneeIds()
    {
        return collect([$this->record->owner_id])
            ->merge($this->record->members()->pluck('users.id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function inviteProjectCollaborator(string $email, string $role): void
    {
        $role = in_array($role, array_keys(Project::projectRoleOptions()), true) ? $role : 'editor';

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (
            strcasecmp((string) $this->record->owner()?->email, $email) === 0
            || ($user && $this->record->members()->whereKey($user->id)->exists())
        ) {
            Notification::make()
                ->title('Collaborator already has access')
                ->body($email.' can already open this project.')
                ->info()
                ->send();

            return;
        }

        $invitation = ProjectInvitation::query()->updateOrCreate(
            [
                'email' => $email,
                'project_id' => $this->record->id,
            ],
            [
                'invited_by' => auth()->id(),
                'role' => 'project_'.$role,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        NotificationFacade::route('mail', $email)
            ->notify(new ProjectInvitationNotification($invitation));

        if ($user) {
            app(ProjectInvitationNotificationService::class)->notifyExistingAccount($invitation, $user);
        }
    }

    public function openTaskCreate(): void
    {
        $this->authorizeManagementModuleMutation('overview', 'new-task', 'New task');
        $this->resetTaskForm();
        $this->showTaskModal = true;
    }

    public function openTaskEdit(int $taskId): void
    {
        $task = $this->record->tasks()->findOrFail($taskId);
        $this->startProjectEditing('overview', $this->taskLockKey($task->id), $this->taskLockLabel($task));
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
        if ($this->editingTaskId) {
            $task = $this->record->tasks()->findOrFail($this->editingTaskId);
            $this->authorizeManagementModuleMutation('overview', $this->taskLockKey($task->id), $this->taskLockLabel($task));
        } else {
            $this->authorizeManagementModuleMutation('overview', 'new-task', 'New task');
        }

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

        if ($wasEditing) {
            $this->stopProjectEditing('overview', $this->taskLockKey($task->id));
        }

        $this->showTaskModal = false;
        $this->resetTaskForm();
        Notification::make()->title($wasEditing ? 'Task updated' : 'Task added')->success()->send();
    }

    public function closeTaskModal(): void
    {
        if ($this->editingTaskId) {
            $this->stopProjectEditing('overview', $this->taskLockKey($this->editingTaskId));
        }

        $this->showTaskModal = false;
        $this->resetTaskForm();
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
        $this->authorizeManagementModuleMutation('overview', 'readiness-tasks', 'Readiness tasks');

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
        $task = $this->record->tasks()->findOrFail($taskId);
        $this->authorizeManagementModuleMutation('overview', $this->taskLockKey($task->id), $this->taskLockLabel($task));
        $task->delete();
        Notification::make()->title('Task deleted')->success()->send();
    }

    protected function taskLockKey(int $taskId): string
    {
        return 'task:'.$taskId;
    }

    protected function taskLockLabel(object $task): string
    {
        return 'Task: '.$task->title;
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
            'application' => ProjectResource::projectUrl($this->record, 'write'),
            'budget' => ProjectResource::projectUrl($this->record, $this->record->implementationModulesAvailable() ? 'board' : 'estimate'),
            'mobility' => ProjectResource::projectUrl($this->record, 'mobility'),
            'participants' => ProjectResource::projectUrl($this->record, 'participants'),
            'documents' => ProjectResource::projectUrl($this->record, 'documents'),
            'settings' => ProjectResource::projectUrl($this->record, 'edit'),
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
            ProjectStatus::Approved, ProjectStatus::Active => $this->getManagementNextStep($urls),
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

    /**
     * Keep the overview's primary call to action aligned with the same
     * readiness checks displayed immediately below it.
     */
    protected function getManagementNextStep(array $urls): array
    {
        $priority = [
            'Project dates' => 10,
            'Grant amount' => 20,
            'Budget baskets' => 30,
            'Overspending' => 40,
            'Mobility dates' => 50,
            'Participant register' => 60,
            'Participant documents' => 70,
            'Participant contact data' => 80,
            'Project file checklist' => 90,
            'Signed generated records' => 100,
            'Expense evidence' => 110,
            'Open tasks' => 120,
        ];

        $issue = collect($this->getProjectReadiness()['items'])
            ->filter(fn (array $item): bool => in_array($item['status'], ['missing', 'attention'], true))
            ->sortBy(fn (array $item): array => [
                $item['severity'] === 'critical' ? 0 : 1,
                $priority[$item['label']] ?? 999,
            ])
            ->first();

        if (! $issue) {
            return [
                'eyebrow' => 'Project is on track',
                'title' => 'Continue managing the project',
                'description' => 'The key setup checks are complete. Keep mobility evidence, expenses and documents up to date as work progresses.',
                'label' => 'Open mobility',
                'url' => $urls['mobility'],
                'icon' => 'heroicon-o-map',
            ];
        }

        $actions = [
            'settings' => ['Complete project settings', 'Open settings', 'heroicon-o-cog-6-tooth'],
            'budget' => ['Review the project budget', 'Open budget', 'heroicon-o-banknotes'],
            'mobility' => ['Set up mobilities', 'Open mobility', 'heroicon-o-map-pin'],
            'participants' => ['Complete participant information', 'Open participants', 'heroicon-o-user-group'],
            'documents' => ['Complete the project file', 'Open documents', 'heroicon-o-document-duplicate'],
            'tasks' => ['Resolve overdue tasks', 'View tasks', 'heroicon-o-check-circle'],
        ];
        [$title, $label, $icon] = $actions[$issue['target']] ?? ['Review project readiness', 'Open overview', 'heroicon-o-clipboard-document-check'];

        return [
            'eyebrow' => $issue['severity'] === 'critical' ? 'Important next step' : 'Recommended next step',
            'title' => $title,
            'description' => $issue['detail'],
            'label' => $label,
            'url' => $urls[$issue['target']] ?? '#project-tasks',
            'icon' => $icon,
        ];
    }

    public function requestTransitionTo(string $target): void
    {
        $this->authorizeManagementModuleMutation('overview', 'project-status', 'Project status');

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

        if ($targetEnum === ProjectStatus::Approved) {
            $this->approvedGrantAmount = $this->record->approvedGrantAmount() > 0
                ? (string) $this->record->approvedGrantAmount()
                : null;
            $this->approvedProjectCode = (string) ($this->record->grant_ref ?? '');
            $this->approvedGrantProof = null;
            $this->pendingTransitionTarget = $targetEnum->value;
            $this->showApprovalModal = true;

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
        $this->authorizeManagementModuleMutation('overview', 'project-status', 'Project status');
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

        if ($targetEnum === ProjectStatus::Approved && ! $this->record->hasDeclaredApprovedGrant()) {
            $this->approvedGrantAmount = $this->record->approvedGrantAmount() > 0
                ? (string) $this->record->approvedGrantAmount()
                : null;
            $this->approvedProjectCode = (string) ($this->record->grant_ref ?? '');
            $this->approvedGrantProof = null;
            $this->pendingTransitionTarget = $targetEnum->value;
            $this->showApprovalModal = true;

            return;
        }

        $this->record->status = $targetEnum->value;
        $this->record->save();

        if ($targetEnum === ProjectStatus::Approved) {
            $this->applyEstimateToBudget();
        }

        $this->record->refresh();

        Notification::make()
            ->title('Status updated to '.$targetEnum->getLabel())
            ->success()
            ->send();
    }

    public function confirmApprovedGrant(): void
    {
        $this->authorizeManagementModuleMutation('overview', 'project-status', 'Project status');

        $proofRequired = blank($this->record->approved_grant_proof_path);

        $this->validate([
            'approvedGrantAmount' => ['required', 'numeric', 'min:1'],
            'approvedProjectCode' => ['required', 'string', 'max:255'],
            'approvedGrantProof' => [
                $proofRequired ? 'required' : 'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ]);

        $current = $this->record->statusEnum();
        if (! $current->canTransitionTo(ProjectStatus::Approved)) {
            Notification::make()
                ->title('This project cannot be marked as approved from '.$current->getLabel())
                ->danger()
                ->send();

            return;
        }

        $proofData = [
            'grant_ref' => trim((string) $this->approvedProjectCode),
        ];

        if ($this->approvedGrantProof) {
            $upload = $this->approvedGrantProof;
            $extension = strtolower($upload->getClientOriginalExtension() ?: 'pdf');
            $directory = 'project-approval-proofs/'.$this->record->id.'/'.Str::uuid();
            $filename = 'approved-grant-proof.'.$extension;
            $path = $directory.'/'.$filename;
            $originalName = $upload->getClientOriginalName();

            app(StoredFileReplacementService::class)->replace(
                disk: 'local',
                path: $path,
                write: fn (): string|false => $upload->storeAs($directory, $filename, 'local'),
                swap: function (StoredFileReference $newFile) use ($proofData, $originalName): StoredFileSwapResult {
                    $lockedProject = Project::query()->whereKey($this->record->id)->lockForUpdate()->firstOrFail();
                    $replacedFile = StoredFileReference::from(
                        $lockedProject->approved_grant_proof_disk,
                        $lockedProject->approved_grant_proof_path,
                    );
                    $lockedProject->forceFill([
                        ...$proofData,
                        'approved_grant_proof_path' => $newFile->path,
                        'approved_grant_proof_disk' => $newFile->disk,
                        'approved_grant_proof_original_name' => $originalName,
                        'approved_grant_proof_uploaded_at' => now(),
                    ])->save();

                    return new StoredFileSwapResult($lockedProject, $replacedFile);
                },
                expectedSize: UploadedFileSize::read($upload),
            );
        } else {
            $this->record->forceFill($proofData)->save();
        }

        $this->record->refresh();
        $this->record->declareApprovedGrant($this->approvedGrantAmount, auth()->user());
        $this->applyEstimateToBudget(seedApprovedGrant: false);

        $this->record->refresh();
        $this->showApprovalModal = false;
        $this->pendingTransitionTarget = null;
        $this->approvedGrantAmount = null;
        $this->approvedProjectCode = null;
        $this->approvedGrantProof = null;

        Notification::make()
            ->title('Project marked as approved')
            ->body($this->record->owner()?->isUnlimitedAccount()
                ? 'The approved grant was locked. Unlimited accounts do not generate project administration fees.'
                : 'The approved grant was declared. All implementation modules are now available, and a 1% fiscal invoice will be issued for payment after the first grant instalment.')
            ->success()
            ->send();
    }

    public function closeApprovalModal(): void
    {
        $this->showApprovalModal = false;
        $this->pendingTransitionTarget = null;
        $this->approvedGrantAmount = null;
        $this->approvedProjectCode = null;
        $this->approvedGrantProof = null;
        $this->resetErrorBag();
    }

    protected function applyEstimateToBudget(bool $seedApprovedGrant = true): void
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

        // Pre-fill requested figures only where still empty. The approved grant
        // is a locked declaration and must not be inferred from the estimator.
        $dirty = false;
        if ($seedApprovedGrant && (float) $this->record->approved_budget <= 0) {
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
            ->body('Review the budget baskets in the Budget board. The approved grant amount remains locked.')
            ->success()
            ->send();
    }
}
