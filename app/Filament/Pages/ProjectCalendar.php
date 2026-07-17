<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ProjectCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Project calendar';

    protected static ?string $slug = 'calendar';

    protected string $view = 'filament.pages.workspace-calendar';

    #[Url]
    public string $month = '';

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_PROJECTS);
    }

    #[Url]
    public string $type = 'all';

    public function mount(): void
    {
        $this->month = $this->validMonth($this->month)->format('Y-m');
    }

    public function getSubheading(): ?string
    {
        return 'Project dates, mobility periods and task deadlines in one shared timeline.';
    }

    public function previousMonth(): void
    {
        $this->month = $this->currentMonth->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->currentMonth->addMonth()->format('Y-m');
    }

    public function today(): void
    {
        $this->month = today()->format('Y-m');
    }

    public function getCurrentMonthProperty(): CarbonImmutable
    {
        return $this->validMonth($this->month);
    }

    public function getCalendarDaysProperty(): Collection
    {
        $month = $this->currentMonth;
        $start = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $events = $this->events($start, $start->addDays(41))->groupBy('date');

        return collect(range(0, 41))->map(function (int $offset) use ($start, $month, $events): array {
            $date = $start->addDays($offset);

            return [
                'date' => $date,
                'current_month' => $date->month === $month->month,
                'today' => $date->isToday(),
                'events' => $events->get($date->toDateString(), collect()),
            ];
        });
    }

    public function getUpcomingProperty(): Collection
    {
        return $this->events(CarbonImmutable::today(), CarbonImmutable::today()->addDays(30))
            ->sortBy('date')
            ->take(8)
            ->values();
    }

    private function events(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $projects = Project::query()
            ->visibleToAccount(auth()->user())
            ->with([
                'tasks' => fn ($query) => $query->whereBetween('due_date', [$start, $end]),
            ])
            ->get();
        $events = collect();

        foreach ($projects as $project) {
            if (in_array($this->type, ['all', 'projects'], true)) {
                $this->addDateEvent($events, $project, $project->start_date, 'Project starts', 'project', 'overview', $start, $end);
                $this->addDateEvent($events, $project, $project->end_date, 'Project ends', 'project', 'overview', $start, $end);
            }
            if (in_array($this->type, ['all', 'mobility'], true)) {
                $this->addDateEvent($events, $project, $project->mobility_start_date, 'Mobility starts', 'mobility', 'participants', $start, $end);
                $this->addDateEvent($events, $project, $project->mobility_end_date, 'Mobility ends', 'mobility', 'participants', $start, $end);
            }
            if (in_array($this->type, ['all', 'tasks'], true)) {
                foreach ($project->tasks as $task) {
                    $date = CarbonImmutable::parse($task->due_date);
                    $events->push([
                        'date' => $date->toDateString(),
                        'title' => $task->title,
                        'project' => $project->name,
                        'kind' => $task->status === 'completed' ? 'completed' : ($date->isBefore(today()) ? 'overdue' : 'task'),
                        'url' => ProjectResource::projectUrl($project).'#project-tasks',
                    ]);
                }
            }
        }

        return $events;
    }

    private function addDateEvent(Collection $events, Project $project, $date, string $title, string $kind, string $page, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $date) {
            return;
        }
        $date = CarbonImmutable::parse($date);
        if (! $date->betweenIncluded($start, $end)) {
            return;
        }
        $events->push([
            'date' => $date->toDateString(),
            'title' => $title,
            'project' => $project->name,
            'kind' => $kind,
            'url' => ProjectResource::projectUrl($project, $page),
        ]);
    }

    private function validMonth(string $month): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                return CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
            } catch (\Throwable) {
                // Fall back to the current month.
            }
        }

        return CarbonImmutable::today()->startOfMonth();
    }
}
