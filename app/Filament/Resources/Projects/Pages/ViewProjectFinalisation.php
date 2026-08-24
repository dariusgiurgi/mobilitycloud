<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectDocument;
use App\Support\AuthorizesProjectManagement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewProjectFinalisation extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-finalisation';

    public array $include = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        ProjectResource::ensureProjectAccountTenant($this->record, 'finalisation');
        $this->authorizeProjectModuleAccess('finalisation');
        $this->hydrateSelection();
    }

    public function getTitle(): string
    {
        return $this->record->name.' — Finalisation';
    }

    public function archiveCategories(): array
    {
        $documents = $this->record->documents();
        $generated = (clone $documents)
            ->whereIn('type', [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT])
            ->count();
        $mobility = (clone $documents)
            ->whereIn('category', array_keys(ProjectDocument::MOBILITY_CATEGORIES))
            ->count();
        $dissemination = (clone $documents)
            ->where('category', 'dissemination_evidence')
            ->count();
        $projectFiles = (clone $documents)
            ->whereNotIn('type', [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT])
            ->whereNotIn('category', array_merge(array_keys(ProjectDocument::MOBILITY_CATEGORIES), ['dissemination_evidence']))
            ->count();

        return [
            'project_data' => ['label' => 'Project data & activity', 'detail' => 'Project summary, tasks and activity record.', 'count' => $this->record->tasks()->count()],
            'application' => ['label' => 'Application', 'detail' => 'Writing answers and application structure.', 'count' => $this->record->applicationSections()->count()],
            'participants' => ['label' => 'Participants', 'detail' => 'Participant records and uploaded participant files.', 'count' => $this->record->participants()->count()],
            'budget' => ['label' => 'Budget & expenses', 'detail' => 'Budget lines, transfers and supporting expense files.', 'count' => $this->record->budgetLines()->count()],
            'agreements' => ['label' => 'Civil conventions', 'detail' => 'Signed agreement and payment evidence attached to expenses.', 'count' => $this->record->budgetLines()->withCount('expenses')->get()->sum('expenses_count')],
            'generated_records' => ['label' => 'Generated records', 'detail' => 'Attendance sheets and expense reports generated in the platform.', 'count' => $generated],
            'project_files' => ['label' => 'Project files', 'detail' => 'Uploaded project documents, agreements and other records.', 'count' => $projectFiles],
            'mobility' => ['label' => 'Mobility evidence', 'detail' => 'Daily evidence, materials, outputs and mobility uploads.', 'count' => $mobility],
            'dissemination' => ['label' => 'Dissemination evidence', 'detail' => 'Organisation-level dissemination files and records.', 'count' => $dissemination],
        ];
    }

    public function selectedCount(): int
    {
        return collect($this->include)->filter()->count();
    }

    public function canConfigureArchive(): bool
    {
        return $this->record->canBeManagedBy(auth()->user());
    }

    public function toggleArchiveCategory(string $key): void
    {
        abort_unless($this->canConfigureArchive(), 403);

        if (! array_key_exists($key, $this->archiveCategories())) {
            return;
        }

        $this->include[$key] = ! ($this->include[$key] ?? false);
        $this->persistSelection();

        Notification::make()
            ->title('Final archive updated')
            ->body('Your archive selection was saved automatically.')
            ->success()
            ->send();
    }

    private function hydrateSelection(): void
    {
        $defaults = array_fill_keys(array_keys($this->archiveCategories()), true);
        $saved = data_get($this->record->action_data, 'finalisation.include');

        $this->include = is_array($saved) && $saved !== []
            ? collect($defaults)->map(fn (bool $value, string $key): bool => (bool) ($saved[$key] ?? false))->all()
            : $defaults;
    }

    private function persistSelection(): void
    {
        $data = $this->record->action_data ?? [];
        $data['finalisation'] = [
            'include' => $this->include,
            'updated_at' => now()->toIso8601String(),
        ];

        $this->record->update(['action_data' => $data]);
    }
}
