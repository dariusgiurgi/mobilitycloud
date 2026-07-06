<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\AccountWorkspaceService;
use App\Services\ProjectDuplicator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.list-projects-cards';

    #[Url]
    public bool $archived = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleArchive')
                ->label(fn (): string => $this->archived ? 'Active projects' : 'Archived projects')
                ->icon(fn (): string => $this->archived ? 'heroicon-o-arrow-left' : 'heroicon-o-archive-box')
                ->color('gray')
                ->action(fn () => $this->archived = ! $this->archived)
                ->visible(fn (): bool => $this->archived
                    || (auth()->user()?->can('create', Project::class) ?? false)
                    || Project::onlyTrashed()
                        ->visibleToAccount(auth()->user())
                        ->exists()),
            Action::make('duplicateProject')
                ->label('Duplicate project')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->modalHeading('Create a reusable project copy')
                ->modalDescription('The new project starts in Writing. Participants, expenses, uploaded files, signed documents, dates and the old grant reference are never copied.')
                ->form([
                    Select::make('source_id')
                        ->label('Source project')
                        ->options(fn (): array => Project::query()
                            ->visibleToAccount(auth()->user())
                            ->orderBy('name')
                            ->with(['workspace.users', 'members'])
                            ->get()
                            ->mapWithKeys(fn (Project $project): array => [
                                $project->id => trim($project->name.' — '.$project->accessLabelFor(auth()->user()).($project->ownerLabelFor(auth()->user()) ? ' · '.$project->ownerLabelFor(auth()->user()) : '')),
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('name')
                        ->label('New project name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Youth Mobility Lab 2027'),
                    Toggle::make('copy_application')
                        ->label('Copy application sections and text')
                        ->default(true),
                    Toggle::make('copy_budget')
                        ->label('Copy estimate and budget basket structure')
                        ->default(true),
                    Toggle::make('copy_partners')
                        ->label('Copy partner organisations')
                        ->default(true),
                ])
                ->action(function (array $data, ProjectDuplicator $duplicator): void {
                    abort_unless(auth()->user()?->can('create', Project::class), 403);
                    $source = Project::query()
                        ->visibleToAccount(auth()->user())
                        ->findOrFail($data['source_id']);
                    $copy = $duplicator->duplicate(
                        $source,
                        $data,
                        app(AccountWorkspaceService::class)->ensureFor(auth()->user()),
                    );

                    Notification::make()
                        ->title('Project copy created')
                        ->body('Review the new dates, acronym and funding details before using it.')
                        ->success()
                        ->send();

                    $this->redirect(ProjectResource::getUrl('overview', ['record' => $copy], tenant: $copy->workspace));
                })
                ->visible(fn (): bool => ! $this->archived
                    && (auth()->user()?->can('create', Project::class) ?? false)
                    && Project::query()->visibleToAccount(auth()->user())->exists()),
            CreateAction::make()
                ->label('New project')
                ->url(fn (): string => ProjectResource::getUrl(
                    'create',
                    tenant: app(AccountWorkspaceService::class)->ensureFor(auth()->user()),
                ))
                ->visible(fn (): bool => ! $this->archived && (auth()->user()?->can('create', Project::class) ?? false)),
        ];
    }

    public function getProjects()
    {
        $query = Project::query()
            ->visibleToAccount(auth()->user())
            ->withCount('participants')
            ->with(['workspace.users', 'members', 'budgetLines.expenses']);

        if ($this->archived) {
            return $query->onlyTrashed()
                ->latest('deleted_at')
                ->get();
        }

        return $query
            ->orderByRaw("CASE status
                WHEN 'active' THEN 0
                WHEN 'approved' THEN 1
                WHEN 'revise' THEN 2
                WHEN 'writing' THEN 3
                WHEN 'submitted' THEN 4
                WHEN 'completed' THEN 5
                WHEN 'rejected' THEN 6
                ELSE 7 END")
            ->latest('updated_at')
            ->get();
    }

    public function restoreProject(int $projectId): void
    {
        $project = Project::onlyTrashed()
            ->visibleToAccount(auth()->user())
            ->with('workspace.users')
            ->findOrFail($projectId);
        abort_unless($project->canManageLifecycleBy(auth()->user()), 403);
        $project->restore();

        Notification::make()
            ->title($project->name.' restored')
            ->success()
            ->send();
    }

    public function getProjectUrl(Project $project): string
    {
        return ProjectResource::getUrl('overview', ['record' => $project], tenant: $project->workspace);
    }
}
