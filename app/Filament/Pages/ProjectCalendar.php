<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
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

    public bool $showEventModal = false;

    public ?int $editingEventId = null;

    public string $eventTitle = '';

    public string $eventDate = '';

    public string $eventProjectId = '';

    public string $eventNotes = '';

    public function mount(): void
    {
        $this->month = $this->validMonth($this->month)->format('Y-m');
    }

    public function getSubheading(): ?string
    {
        return 'Project dates, mobility periods, task deadlines and your own planning dates in one timeline.';
    }

    public function openCreateEvent(): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $this->resetEventForm();
        $this->eventDate = $this->currentMonth->isCurrentMonth()
            ? today()->toDateString()
            : $this->currentMonth->toDateString();
        $this->showEventModal = true;
    }

    public function openEditEvent(int $eventId): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $event = $this->eventQuery()->findOrFail($eventId);

        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventDate = $event->event_date->toDateString();
        $this->eventProjectId = $event->project_id ? (string) $event->project_id : '';
        $this->eventNotes = $event->notes ?: '';
        $this->showEventModal = true;
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->resetEventForm();
        $this->resetValidation();
    }

    public function saveEvent(): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        $data = $this->validate([
            'eventTitle' => ['required', 'string', 'max:160'],
            'eventDate' => ['required', 'date'],
            'eventProjectId' => ['nullable', 'integer'],
            'eventNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $projectId = filled($data['eventProjectId']) ? (int) $data['eventProjectId'] : null;
        if ($projectId && ! Project::query()->visibleToAccount(auth()->user())->whereKey($projectId)->exists()) {
            abort(403);
        }

        $payload = [
            'project_id' => $projectId,
            'title' => trim($data['eventTitle']),
            'event_date' => $data['eventDate'],
            'notes' => filled($data['eventNotes']) ? trim($data['eventNotes']) : null,
        ];

        $isEditing = (bool) $this->editingEventId;
        if ($isEditing) {
            $event = $this->eventQuery()->findOrFail($this->editingEventId);
            $event->update($payload);
        } else {
            $event = CalendarEvent::create($payload + ['user_id' => auth()->id()]);
        }

        $this->month = CarbonImmutable::parse($data['eventDate'])->format('Y-m');
        $this->closeEventModal();

        Notification::make()
            ->title($isEditing ? 'Custom date updated' : 'Custom date added')
            ->body($event->title.' is now in your calendar.')
            ->success()
            ->send();
    }

    public function deleteEvent(): void
    {
        abort_if(PlatformAccess::isReadOnly(), 403);
        abort_unless($this->editingEventId, 404);

        $this->eventQuery()->findOrFail($this->editingEventId)->delete();
        $this->closeEventModal();

        Notification::make()
            ->title('Custom date deleted')
            ->success()
            ->send();
    }

    public function getEventProjectOptionsProperty(): Collection
    {
        return Project::query()
            ->visibleToAccount(auth()->user())
            ->orderBy('name')
            ->pluck('name', 'id');
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
                'mobilities',
            ])
            ->get();
        $events = collect();

        foreach ($projects as $project) {
            if (in_array($this->type, ['all', 'projects'], true)) {
                $this->addDateEvent($events, $project, $project->start_date, 'Project starts', 'project', 'overview', $start, $end);
                $this->addDateEvent($events, $project, $project->end_date, 'Project ends', 'project', 'overview', $start, $end);
            }
            if (in_array($this->type, ['all', 'mobility'], true)) {
                if ($project->mobilities->isNotEmpty()) {
                    foreach ($project->mobilities as $mobility) {
                        $name = $mobility->name ?: 'Mobility';
                        $this->addDateEvent($events, $project, $mobility->start_date, $name.' starts', 'mobility', 'participants', $start, $end);
                        $this->addDateEvent($events, $project, $mobility->end_date, $name.' ends', 'mobility', 'participants', $start, $end);
                    }
                } else {
                    $this->addDateEvent($events, $project, $project->mobility_start_date, 'Mobility starts', 'mobility', 'participants', $start, $end);
                    $this->addDateEvent($events, $project, $project->mobility_end_date, 'Mobility ends', 'mobility', 'participants', $start, $end);
                }
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
                        'custom' => false,
                    ]);
                }
            }
        }

        if (in_array($this->type, ['all', 'custom'], true)) {
            $this->customEvents($start, $end)->each(function (CalendarEvent $event) use ($events): void {
                $events->push([
                    'date' => $event->event_date->toDateString(),
                    'title' => $event->title,
                    'project' => $event->project?->name ?: 'My custom date',
                    'kind' => 'custom',
                    'url' => null,
                    'custom' => true,
                    'id' => $event->id,
                    'notes' => $event->notes,
                ]);
            });
        }

        return $events->sortBy([
            ['date', 'asc'],
            ['title', 'asc'],
        ])->values();
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
            'custom' => false,
        ]);
    }

    private function customEvents(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->eventQuery()
            ->with('project')
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('event_date')
            ->orderBy('title')
            ->get();
    }

    private function eventQuery()
    {
        return CalendarEvent::query()->where('user_id', auth()->id());
    }

    private function resetEventForm(): void
    {
        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventDate = '';
        $this->eventProjectId = '';
        $this->eventNotes = '';
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
