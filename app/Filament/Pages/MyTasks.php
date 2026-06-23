<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectTask;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class MyTasks extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $navigationLabel = 'My Tasks';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'My Tasks';

    protected string $view = 'filament.pages.my-tasks';

    public string $statusFilter = 'open';

    public static function canAccess(): bool
    {
        return PlatformAccess::usesWorkspaceInterface();
    }

    public string $dueFilter = 'all';

    public string $search = '';

    public static function getNavigationBadge(): ?string
    {
        $count = static::taskQueryForCurrentUser()->where('status', 'open')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $hasOverdue = static::taskQueryForCurrentUser()
            ->where('status', 'open')
            ->whereDate('due_date', '<', today())
            ->exists();

        return $hasOverdue ? 'danger' : 'primary';
    }

    public function getSubheading(): ?string
    {
        return 'Your assigned actions across every project in this workspace.';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['statusFilter', 'dueFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function getTasks()
    {
        return $this->filteredQuery()
            ->with('project')
            ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest('id')
            ->paginate(20);
    }

    public function getStats(): array
    {
        $tasks = static::taskQueryForCurrentUser()->get(['status', 'due_date']);
        $open = $tasks->where('status', 'open');

        return [
            'open' => $open->count(),
            'overdue' => $open->filter(fn (ProjectTask $task): bool => $task->due_date?->isBefore(today()) ?? false)->count(),
            'next_seven_days' => $open->filter(fn (ProjectTask $task): bool => $task->due_date
                && $task->due_date->betweenIncluded(today(), today()->addDays(7)))->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
        ];
    }

    public function toggleTask(int $taskId): void
    {
        $task = static::taskQueryForCurrentUser()->findOrFail($taskId);
        abort_unless($task->canBeCompletedBy(auth()->user()), 403);
        $completed = ! $task->isCompleted();

        $task->update([
            'status' => $completed ? 'completed' : 'open',
            'completed_at' => $completed ? now() : null,
            'completed_by' => $completed ? auth()->id() : null,
            'reminder_sent_at' => $completed ? $task->reminder_sent_at : null,
            'overdue_notified_at' => $completed ? $task->overdue_notified_at : null,
        ]);

        Notification::make()
            ->title($completed ? 'Task completed' : 'Task reopened')
            ->success()
            ->send();
    }

    public function getProjectUrl(ProjectTask $task): string
    {
        return ProjectResource::getUrl('overview', ['record' => $task->project]).'#project-tasks';
    }

    private function filteredQuery(): Builder
    {
        return static::taskQueryForCurrentUser()
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('name', 'like', $term));
                });
            })
            ->when($this->dueFilter === 'overdue', fn (Builder $query) => $query->where('status', 'open')->whereDate('due_date', '<', today()))
            ->when($this->dueFilter === 'today', fn (Builder $query) => $query->whereDate('due_date', today()))
            ->when($this->dueFilter === 'week', fn (Builder $query) => $query->whereBetween('due_date', [today(), today()->addDays(7)]))
            ->when($this->dueFilter === 'none', fn (Builder $query) => $query->whereNull('due_date'));
    }

    private static function taskQueryForCurrentUser(): Builder
    {
        return ProjectTask::query()
            ->where('assigned_to', auth()->id())
            ->whereHas('project', fn (Builder $query) => $query
                ->where('workspace_id', Filament::getTenant()?->id)
                ->accessibleTo(auth()->user(), Filament::getTenant()));
    }
}
