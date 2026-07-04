<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ProjectDuplicator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
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
                    || (Filament::getTenant()?->canBeManagedBy(auth()->user()) ?? false)
                    || Project::onlyTrashed()
                        ->where('workspace_id', Filament::getTenant()?->id)
                        ->accessibleTo(auth()->user(), Filament::getTenant())
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
                            ->where('workspace_id', Filament::getTenant()?->id)
                            ->accessibleTo(auth()->user(), Filament::getTenant())
                            ->orderBy('name')
                            ->pluck('name', 'id')
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
                    $workspace = Filament::getTenant();
                    abort_unless(auth()->user()?->can('create', Project::class), 403);
                    $source = Project::query()
                        ->where('workspace_id', $workspace->id)
                        ->accessibleTo(auth()->user(), $workspace)
                        ->findOrFail($data['source_id']);
                    $copy = $duplicator->duplicate($source, $data);

                    Notification::make()
                        ->title('Project copy created')
                        ->body('Review the new dates, acronym and funding details before using it.')
                        ->success()
                        ->send();

                    $this->redirect(ProjectResource::getUrl('overview', ['record' => $copy]));
                })
                ->visible(fn (): bool => ! $this->archived
                    && (auth()->user()?->can('create', Project::class) ?? false)
                    && Project::query()->where('workspace_id', Filament::getTenant()?->id)
                        ->accessibleTo(auth()->user(), Filament::getTenant())->exists()),
            CreateAction::make()
                ->label('New project')
                ->visible(fn (): bool => ! $this->archived && (auth()->user()?->can('create', Project::class) ?? false)),
        ];
    }

    public function getProjects()
    {
        $query = Project::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->accessibleTo(auth()->user(), Filament::getTenant())
            ->withCount('participants')
            ->with('budgetLines.expenses');

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
        $workspace = Filament::getTenant();
        abort_unless($workspace?->canBeManagedBy(auth()->user()), 403);
        $project = Project::onlyTrashed()
            ->where('workspace_id', $workspace->id)
            ->accessibleTo(auth()->user(), $workspace)
            ->findOrFail($projectId);
        $project->restore();

        Notification::make()
            ->title($project->name.' restored')
            ->success()
            ->send();
    }

    public function getProjectUrl(Project $project): string
    {
        return ProjectResource::getUrl('overview', ['record' => $project]);
    }
}
