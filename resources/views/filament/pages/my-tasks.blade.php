<x-filament-panels::page>
    <x-ui-polish />

    @php
        $stats = $this->getStats();
        $tasks = $this->getTasks();
    @endphp

    <style>
        .mc-my-stats { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem; }
        .mc-my-stat { padding:1rem;border:1px solid rgba(148,163,184,.2);border-radius:.75rem;background:#fff; }
        .mc-my-filters { display:grid;grid-template-columns:minmax(220px,1fr) 160px 180px;gap:.65rem; }
        .mc-my-field { width:100%;padding:.55rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.55rem;background:transparent;font-size:.8rem; }
        .mc-my-task { display:flex;align-items:flex-start;gap:.8rem;padding:1rem; }
        .mc-my-task + .mc-my-task { border-top:1px solid rgba(148,163,184,.16); }
        .mc-my-check { width:24px;height:24px;display:flex;align-items:center;justify-content:center;flex:none;border:1px solid rgba(100,116,139,.35);border-radius:9999px;background:transparent;color:#fff;cursor:pointer; }
        .mc-my-check.is-done { border-color:#10b981;background:#10b981; }
        .mc-my-muted { color:#64748b; }
        .dark .mc-my-stat { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-my-muted { color:#94a3b8; }
        @media (max-width:800px) { .mc-my-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .mc-my-filters { grid-template-columns:1fr; } }
    </style>

    <div style="display:grid;gap:1rem;">
        <div class="mc-my-stats">
            <div class="mc-my-stat">
                <p class="mc-my-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Open</p>
                <p class="text-gray-950 dark:text-white" style="font-size:1.45rem;font-weight:700;margin-top:.2rem;">{{ $stats['open'] }}</p>
            </div>
            <div class="mc-my-stat">
                <p class="mc-my-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Overdue</p>
                <p style="font-size:1.45rem;font-weight:700;margin-top:.2rem;color:{{ $stats['overdue'] > 0 ? '#dc2626' : 'inherit' }};">{{ $stats['overdue'] }}</p>
            </div>
            <div class="mc-my-stat">
                <p class="mc-my-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Next 7 days</p>
                <p class="text-gray-950 dark:text-white" style="font-size:1.45rem;font-weight:700;margin-top:.2rem;">{{ $stats['next_seven_days'] }}</p>
            </div>
            <div class="mc-my-stat">
                <p class="mc-my-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Completed</p>
                <p class="text-gray-950 dark:text-white" style="font-size:1.45rem;font-weight:700;margin-top:.2rem;">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <x-filament::section>
            <div class="mc-my-filters">
                <input type="search" wire:model.live.debounce.300ms="search" class="mc-my-field text-gray-950 dark:text-white" placeholder="Search task or project…" aria-label="Search tasks">
                <select wire:model.live="statusFilter" class="mc-my-field text-gray-950 dark:text-white" aria-label="Task status">
                    <option value="open">Open tasks</option>
                    <option value="completed">Completed</option>
                    <option value="all">All statuses</option>
                </select>
                <select wire:model.live="dueFilter" class="mc-my-field text-gray-950 dark:text-white" aria-label="Task deadline">
                    <option value="all">Any deadline</option>
                    <option value="overdue">Overdue</option>
                    <option value="today">Due today</option>
                    <option value="week">Next 7 days</option>
                    <option value="none">No deadline</option>
                </select>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Assigned to me</x-slot>
            <x-slot name="description">{{ $tasks->total() }} {{ str('task')->plural($tasks->total()) }} match the current view.</x-slot>

            @forelse($tasks as $task)
                <div class="mc-my-task" wire:key="my-task-{{ $task->id }}">
                    <button type="button" wire:click="toggleTask({{ $task->id }})" class="mc-my-check {{ $task->isCompleted() ? 'is-done' : '' }}" aria-label="{{ $task->isCompleted() ? 'Reopen' : 'Complete' }} {{ $task->title }}">
                        @if($task->isCompleted())<x-filament::icon icon="heroicon-m-check" style="width:.8rem;height:.8rem;" />@endif
                    </button>

                    <div style="min-width:0;flex:1;">
                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                            <a href="{{ $this->getProjectUrl($task) }}" class="text-gray-950 dark:text-white" style="font-size:.84rem;font-weight:650;text-decoration:none;{{ $task->isCompleted() ? 'text-decoration:line-through;opacity:.65;' : '' }}">{{ $task->title }}</a>
                            @if($task->priority === 'high')<x-filament::badge color="danger" size="sm">High</x-filament::badge>@endif
                            @if($task->isOverdue())<x-filament::badge color="danger" size="sm">Overdue</x-filament::badge>@endif
                        </div>
                        @if($task->description)
                            <p class="mc-my-muted" style="font-size:.73rem;line-height:1.45;margin-top:.25rem;">{{ $task->description }}</p>
                        @endif
                        <div class="mc-my-muted" style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;font-size:.69rem;margin-top:.4rem;">
                            <a href="{{ $this->getProjectUrl($task) }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">{{ $task->project->name }}</a>
                            <span>{{ $task->due_date ? 'Due '.$task->due_date->format('d M Y') : 'No deadline' }}</span>
                            <span>{{ ucfirst($task->priority) }} priority</span>
                        </div>
                    </div>

                    <x-filament::icon-button tag="a" :href="$this->getProjectUrl($task)" icon="heroicon-m-arrow-top-right-on-square" color="gray" size="sm" label="Open project" />
                </div>
            @empty
                <div style="padding:2rem 1rem;text-align:center;">
                    <x-filament::icon icon="heroicon-o-check-circle" class="mx-auto h-9 w-9 text-gray-400" />
                    <p class="text-gray-950 dark:text-white" style="font-size:.86rem;font-weight:650;margin-top:.6rem;">{{ $search || $dueFilter !== 'all' || $statusFilter !== 'open' ? 'No matching tasks' : 'No assigned tasks yet' }}</p>
                    <p class="mc-my-muted" style="font-size:.75rem;line-height:1.5;margin:.2rem auto 0;max-width:34rem;">
                        {{ $search || $dueFilter !== 'all' || $statusFilter !== 'open' ? 'Try another filter or open a project to review its task board.' : 'Tasks are created inside a project overview. Once someone assigns a task to you, it will appear here automatically.' }}
                    </p>
                    <div style="margin-top:.85rem;">
                        <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::getUrl()" color="gray" size="sm" icon="heroicon-o-rectangle-stack">
                            Open projects
                        </x-filament::button>
                    </div>
                </div>
            @endforelse

            @if($tasks->hasPages())
                <div style="margin-top:1rem;">{{ $tasks->links() }}</div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
