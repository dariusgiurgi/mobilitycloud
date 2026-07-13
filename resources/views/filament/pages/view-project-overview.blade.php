<x-filament-panels::page>
    <x-ui-polish />
    @php
        $status = $this->getStatusEnum();
        $transitions = $status->allowedTransitions();
        $application = $this->getApplicationSummary();
        $participants = $this->getParticipantSummary();
        $documents = $this->getDocumentSummary();
        $activity = $this->getRecentActivity();
        $tasks = $this->getProjectTasks();
        $taskAssignees = $this->getTaskAssignees();
        $nextStep = $this->getNextStep();
        $urls = $this->getModuleUrls();
        $readiness = $this->getProjectReadiness();
        $readinessTargets = [
            'application' => $urls['application'],
            'budget' => $urls['budget'],
            'participants' => $urls['participants'],
            'documents' => $urls['documents'],
            'settings' => $urls['settings'],
            'tasks' => '#project-tasks',
        ];
        $partners = $this->record->partners;
        $approved = (float) $this->record->approved_budget;
        $requested = (float) $this->record->total_budget;
        $ka = \App\Support\ApplicationTemplates::normaliseKey($this->record->ka_action);
        $kaInfo = $ka ? \App\Support\ApplicationTemplates::get($ka) : null;
        $kaLabel = $kaInfo ? ($kaInfo['label'].' · Call '.$kaInfo['call_year']) : null;
        $isManagementStage = $this->record->isManagementStage();
        $budgetModuleLabel = $isManagementStage ? 'Budget' : 'Grant estimate';
        $canManage = $this->record->canBeManagedBy(auth()->user());
        $ownerLabel = $this->record->ownerLabelFor(auth()->user());
        $accessLabel = $this->record->accessLabelFor(auth()->user());
        $eur = fn ($value) => number_format((float) $value, 2, '.', ',').' €';
    @endphp

    <style>
        .mc-overview-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem; }
        .mc-overview-card { display:flex;flex-direction:column;min-height:190px;padding:1rem;border:1px solid rgba(148,163,184,.22);border-radius:.8rem;text-decoration:none;background:#fff;transition:transform .15s,border-color .15s,box-shadow .15s; }
        .mc-overview-card:hover { transform:translateY(-2px);border-color:rgba(99,102,241,.45);box-shadow:0 10px 28px rgba(15,23,42,.08); }
        .mc-overview-muted { color:#64748b; }
        .mc-overview-detail-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.1rem 1.5rem; }
        .mc-activity-row { position:relative;display:flex;gap:.8rem;padding:0 0 1rem; }
        .mc-activity-row:not(:last-child)::after { content:'';position:absolute;left:14px;top:30px;bottom:0;width:1px;background:rgba(148,163,184,.25); }
        .mc-activity-icon { width:29px;height:29px;display:flex;align-items:center;justify-content:center;flex:none;border-radius:9999px;background:color-mix(in srgb,var(--activity-color) 11%,transparent);color:var(--activity-color); }
        .mc-task-row { display:flex;align-items:flex-start;gap:.75rem;padding:.8rem 0; }
        .mc-task-row + .mc-task-row { border-top:1px solid rgba(148,163,184,.16); }
        .mc-task-check { width:22px;height:22px;display:flex;align-items:center;justify-content:center;flex:none;border:1px solid rgba(100,116,139,.35);border-radius:9999px;background:transparent;color:#fff;cursor:pointer; }
        .mc-task-check.is-done { border-color:#10b981;background:#10b981; }
        .mc-task-field { width:100%;padding:.58rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.55rem;background:transparent;font-size:.82rem; }
        .mc-task-form-grid { display:grid;grid-template-columns:1fr 1fr;gap:.85rem; }
        .mc-readiness-panel { margin-top:1rem;padding:1rem;border:1px solid rgba(99,102,241,.18);border-radius:.95rem;background:linear-gradient(135deg,rgba(99,102,241,.09),rgba(14,165,233,.045)); }
        .mc-readiness-head { display:grid;grid-template-columns:150px minmax(0,1fr) auto;gap:1rem;align-items:center; }
        .mc-readiness-score { width:118px;height:118px;border-radius:999px;display:grid;place-items:center;background:conic-gradient(var(--mc-readiness-color) calc(var(--mc-readiness-score) * 1%),rgba(148,163,184,.2) 0);position:relative; }
        .mc-readiness-score::after { content:'';position:absolute;inset:9px;border-radius:999px;background:#fff; }
        .mc-readiness-score span { position:relative;z-index:1;font-size:1.55rem;font-weight:850;color:#111827; }
        .mc-readiness-groups { display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.45rem;margin-top:.9rem; }
        .mc-readiness-group { border:1px solid rgba(148,163,184,.2);border-radius:.65rem;padding:.55rem .6rem;background:rgba(255,255,255,.62); }
        .mc-readiness-issues { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem;margin-top:.8rem; }
        .mc-readiness-issue { display:flex;gap:.55rem;align-items:flex-start;padding:.65rem .7rem;border:1px solid rgba(148,163,184,.2);border-radius:.7rem;background:rgba(255,255,255,.72);text-decoration:none; }
        .mc-readiness-dot { width:18px;height:18px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;flex:none;font-size:.62rem;font-weight:850; }
        .dark .mc-overview-card { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-overview-card:hover { box-shadow:0 10px 28px rgba(0,0,0,.22); }
        .dark .mc-overview-muted { color:#94a3b8; }
        .dark .mc-readiness-score::after { background:rgb(17,24,39); }
        .dark .mc-readiness-score span { color:#f9fafb; }
        .dark .mc-readiness-group,.dark .mc-readiness-issue { background:rgba(17,24,39,.68);border-color:rgba(255,255,255,.1); }
        @media (max-width:1100px) { .mc-overview-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:900px) { .mc-readiness-head { grid-template-columns:1fr; }.mc-readiness-groups { grid-template-columns:repeat(2,minmax(0,1fr)); }.mc-readiness-issues { grid-template-columns:1fr; } }
        @media (max-width:700px) { .mc-overview-grid,.mc-overview-detail-grid,.mc-task-form-grid { grid-template-columns:1fr; } }
    </style>

    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;">
                <span class="mc-overview-muted" style="font-size:.78rem;font-weight:600;">Project stage</span>
                <x-filament::badge :color="$status->getColor()" size="lg">{{ $status->getLabel() }}</x-filament::badge>
                @if ($ownerLabel)
                    <x-filament::badge color="gray">{{ $ownerLabel }}</x-filament::badge>
                    <x-filament::badge color="{{ $accessLabel === 'Viewer' ? 'warning' : 'info' }}">{{ $accessLabel }}</x-filament::badge>
                @else
                    <x-filament::badge color="success">{{ $accessLabel }}</x-filament::badge>
                @endif
                <x-help-tip id="project-lifecycle" title="Project stage">
                    Status controls the recommended workflow and whether Budget opens the grant estimator or the implementation board. Status changes follow the allowed project lifecycle and do not delete project data.
                </x-help-tip>
                @if ($kaLabel)
                    <x-filament::badge color="gray">{{ strtoupper($ka) }}</x-filament::badge>
                @endif
                @if ($this->record->is_activated)
                    <x-filament::badge color="success">Activated</x-filament::badge>
                @endif
            </div>

            @if ($canManage)
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                    @forelse ($transitions as $next)
                        <x-filament::button
                            wire:click="requestTransitionTo('{{ $next->value }}')"
                            :color="$next->getColor()"
                            size="sm"
                        >
                            Mark as {{ $next->getLabel() }}
                        </x-filament::button>
                    @empty
                        <span class="mc-overview-muted" style="font-size:.76rem;">Lifecycle complete</span>
                    @endforelse
                </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section style="margin-top:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:flex-start;gap:.85rem;max-width:760px;">
                <span style="width:42px;height:42px;display:inline-flex;align-items:center;justify-content:center;flex:none;border-radius:.7rem;background:rgba(99,102,241,.1);color:#6366f1;">
                    <x-filament::icon :icon="$nextStep['icon']" style="width:1.3rem;height:1.3rem;" />
                </span>
                <div>
                    <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6366f1;">{{ $nextStep['eyebrow'] }}</p>
                    <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;margin-top:.15rem;">{{ $nextStep['title'] }}</h2>
                    <p class="mc-overview-muted" style="font-size:.78rem;line-height:1.5;margin-top:.25rem;">{{ $nextStep['description'] }}</p>
                </div>
            </div>
            @if ($nextStep['url'] && $nextStep['label'])
                <x-filament::button tag="a" :href="$nextStep['url']" icon="heroicon-m-arrow-right" icon-position="after" size="sm">
                    {{ $nextStep['label'] }}
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @php
        $readinessColor = match ($readiness['tone']) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            default => '#ef4444',
        };
        $visibleReadinessIssues = collect($readiness['items'])
            ->filter(fn ($item) => in_array($item['status'], ['missing', 'attention'], true))
            ->take(3);
    @endphp
    <section class="mc-readiness-panel" style="--mc-readiness-score:{{ $readiness['score'] }};--mc-readiness-color:{{ $readinessColor }};">
        <div class="mc-readiness-head">
            <div class="mc-readiness-score" aria-label="Project readiness {{ $readiness['score'] }} percent">
                <span>{{ $readiness['score'] }}%</span>
            </div>
            <div style="min-width:0;">
                <p style="font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6366f1;">Project readiness check</p>
                <h2 class="text-gray-950 dark:text-white" style="font-size:1.03rem;font-weight:750;margin-top:.18rem;">{{ $readiness['status'] }}</h2>
                <p class="mc-overview-muted" style="font-size:.76rem;line-height:1.5;margin-top:.28rem;">
                    Checks application, dates, grant/budget, participants, documents, signed records and open tasks. It adapts to the current project stage.
                </p>
                <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.55rem;">
                    <x-filament::badge color="success">{{ $readiness['complete'] }} complete</x-filament::badge>
                    @if($readiness['critical'])<x-filament::badge color="danger">{{ $readiness['critical'] }} critical</x-filament::badge>@endif
                    @if($readiness['warning'])<x-filament::badge color="warning">{{ $readiness['warning'] }} warnings</x-filament::badge>@endif
                    @if($readiness['optional'])<x-filament::badge color="gray">{{ $readiness['optional'] }} optional</x-filament::badge>@endif
                </div>
            </div>
            <div style="display:flex;gap:.45rem;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                @if($readiness['next'])
                    <x-filament::button tag="a" :href="$readinessTargets[$readiness['next']['target']] ?? '#'" color="{{ $readiness['tone'] === 'success' ? 'gray' : 'primary' }}" icon="heroicon-m-arrow-right" icon-position="after" size="sm">
                        Fix next item
                    </x-filament::button>
                @endif
                @if($canManage && ($readiness['critical'] + $readiness['warning']) > 0)
                    <x-filament::button wire:click="createTasksFromReadiness" wire:loading.attr="disabled" color="gray" icon="heroicon-m-list-bullet" size="sm">
                        Create tasks
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="mc-readiness-groups">
            @foreach($readiness['groups'] as $group)
                <div class="mc-readiness-group">
                    <div style="display:flex;justify-content:space-between;gap:.45rem;align-items:center;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.68rem;font-weight:780;line-height:1.2;">{{ $group['label'] }}</p>
                        <span style="font-size:.72rem;font-weight:850;color:{{ $group['issues'] ? '#d97706' : '#059669' }};">{{ $group['score'] }}%</span>
                    </div>
                    <div style="height:4px;border-radius:999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:.45rem;">
                        <div style="height:100%;width:{{ $group['score'] }}%;background:{{ $group['issues'] ? '#f59e0b' : '#10b981' }};"></div>
                    </div>
                    <p class="mc-overview-muted" style="font-size:.6rem;margin-top:.34rem;">{{ $group['complete'] }} done · {{ $group['issues'] }} issue{{ $group['issues'] === 1 ? '' : 's' }}</p>
                </div>
            @endforeach
        </div>

        @if($visibleReadinessIssues->isNotEmpty())
            <div class="mc-readiness-issues">
                @foreach($visibleReadinessIssues as $item)
                    @php
                        $isCritical = $item['severity'] === 'critical';
                        $dotBg = $item['status'] === 'missing' ? 'rgba(239,68,68,.12)' : 'rgba(245,158,11,.14)';
                        $dotColor = $item['status'] === 'missing' ? '#dc2626' : '#b45309';
                    @endphp
                    <a class="mc-readiness-issue" href="{{ $readinessTargets[$item['target']] ?? '#' }}">
                        <span class="mc-readiness-dot" style="background:{{ $dotBg }};color:{{ $dotColor }};">{{ $isCritical ? '!' : '•' }}</span>
                        <span style="min-width:0;">
                            <span class="text-gray-950 dark:text-white" style="display:block;font-size:.72rem;font-weight:780;line-height:1.3;">{{ $item['label'] }}</span>
                            <span class="mc-overview-muted" style="display:block;font-size:.65rem;line-height:1.35;margin-top:.12rem;">{{ $item['detail'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div style="margin-top:.8rem;padding:.65rem .75rem;border-radius:.7rem;background:rgba(16,185,129,.1);color:#047857;font-size:.72rem;font-weight:700;">
                No blocking readiness issues detected.
            </div>
        @endif
    </section>

    <div class="mc-overview-grid" style="margin-top:1rem;">
        <a href="{{ $urls['application'] }}" class="mc-overview-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;">
                <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:.55rem;background:rgba(99,102,241,.1);color:#6366f1;">
                    <x-filament::icon icon="heroicon-o-document-text" style="width:1.05rem;height:1.05rem;" />
                </span>
                <span class="mc-overview-muted" style="font-size:.68rem;">{{ $application['completed'] }}/{{ $application['total'] }} sections</span>
            </div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:650;margin-top:.85rem;">Application</h3>
            <p class="mc-overview-muted" style="font-size:.73rem;line-height:1.45;margin-top:.2rem;">{{ $application['total'] > 0 ? $application['progress'].'% of sections contain text' : 'No application template loaded' }}</p>
            <div style="height:6px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:auto;">
                <div style="height:100%;width:{{ $application['progress'] }}%;border-radius:9999px;background:#6366f1;"></div>
            </div>
            <span style="font-size:.72rem;font-weight:600;color:#6366f1;margin-top:.55rem;">Open application →</span>
        </a>

        <a href="{{ $urls['budget'] }}" class="mc-overview-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;">
                <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:.55rem;background:rgba(16,185,129,.1);color:#059669;">
                    <x-filament::icon icon="heroicon-o-banknotes" style="width:1.05rem;height:1.05rem;" />
                </span>
                <span class="mc-overview-muted" style="font-size:.68rem;">{{ $isManagementStage ? $this->record->progress.'% spent' : 'Estimate' }}</span>
            </div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:650;margin-top:.85rem;">{{ $budgetModuleLabel }}</h3>
            @if (! $isManagementStage)
                <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $eur($this->record->effective_budget) }}</p>
                <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">Requested grant currently saved</p>
            @else
                <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $eur($this->record->remaining) }}</p>
                <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">remaining from {{ $eur($this->record->effective_budget) }}</p>
            @endif
            <span style="font-size:.72rem;font-weight:600;color:#6366f1;margin-top:auto;padding-top:.7rem;">Open {{ \Illuminate\Support\Str::lower($budgetModuleLabel) }} →</span>
        </a>

        <a href="{{ $urls['participants'] }}" class="mc-overview-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;">
                <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:.55rem;background:rgba(245,158,11,.1);color:#d97706;">
                    <x-filament::icon icon="heroicon-o-users" style="width:1.05rem;height:1.05rem;" />
                </span>
                @if ($participants['incomplete'] > 0)
                    <x-filament::badge color="warning" size="sm">{{ $participants['incomplete'] }} incomplete</x-filament::badge>
                @endif
            </div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:650;margin-top:.85rem;">Participants</h3>
            <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $participants['total'] }}</p>
            <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">
                {{ $isManagementStage ? ($participants['total'] > 0 ? $participants['complete'].' records have all required files' : 'No participants added yet') : 'Available after project approval' }}
            </p>
            <span style="font-size:.72rem;font-weight:600;color:{{ $isManagementStage ? '#6366f1' : '#94a3b8' }};margin-top:auto;padding-top:.7rem;">{{ $isManagementStage ? 'Open participants →' : 'Locked until approval' }}</span>
        </a>

        <a href="{{ $urls['documents'] }}" class="mc-overview-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;">
                <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:.55rem;background:rgba(59,130,246,.1);color:#2563eb;">
                    <x-filament::icon icon="heroicon-o-document-duplicate" style="width:1.05rem;height:1.05rem;" />
                </span>
                @if ($documents['issues'] > 0)
                    <x-filament::badge color="warning" size="sm">{{ $documents['issues'] }} issues</x-filament::badge>
                @endif
            </div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:650;margin-top:.85rem;">Documents</h3>
            <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $documents['files'] }} files</p>
            <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">
                {{ $isManagementStage ? ($documents['checklist_applies'] ? $documents['complete'].' checklist items complete' : 'Project files and generated records') : 'Available after project approval' }}
            </p>
            <span style="font-size:.72rem;font-weight:600;color:{{ $isManagementStage ? '#6366f1' : '#94a3b8' }};margin-top:auto;padding-top:.7rem;">{{ $isManagementStage ? 'Open documents →' : 'Locked until approval' }}</span>
        </a>
    </div>

    <x-filament::section style="margin-top:1rem;">
        <x-slot name="heading">Project details</x-slot>
        <x-slot name="headerEnd">
            @if ($canManage)
                <x-filament::button tag="a" :href="$urls['settings']" color="gray" size="sm" icon="heroicon-o-cog-6-tooth">Settings</x-filament::button>
            @endif
        </x-slot>

        <div class="mc-overview-detail-grid">
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Action</p>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ $kaLabel ?: 'Not configured' }}</p>
            </div>
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Grant reference</p>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ $this->record->grant_ref ?: 'Not configured' }}</p>
            </div>
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Funding</p>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ $approved > 0 ? $eur($approved).' approved' : $eur($requested).' requested' }}</p>
            </div>
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Project period</p>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ $this->record->start_date?->format('d M Y') ?? '—' }} → {{ $this->record->end_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Mobility period</p>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ $this->record->mobility_start_date?->format('d M Y') ?? '—' }} → {{ $this->record->mobility_end_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="mc-overview-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;">Partner organisations</p>
                @if (count($partners) > 0)
                    <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">{{ collect($partners)->pluck('name')->filter()->join(', ') }}</p>
                @else
                    <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:550;margin-top:.2rem;">Not configured</p>
                @endif
            </div>
        </div>
    </x-filament::section>

    <x-filament::section id="project-tasks" style="margin-top:1rem;">
        <x-slot name="heading">Project tasks</x-slot>
        <x-slot name="description">Operational actions, owners and deadlines for this project.</x-slot>
        <x-slot name="headerEnd">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <select wire:model.live="taskFilter" class="mc-task-field text-gray-950 dark:text-white" style="width:auto;padding:.4rem 1.8rem .4rem .55rem;font-size:.72rem;">
                    <option value="open">Open</option>
                    <option value="completed">Completed</option>
                    <option value="all">All</option>
                </select>
                @if($canManage)
                    <x-filament::button wire:click="openTaskCreate" size="sm" icon="heroicon-o-plus">Add task</x-filament::button>
                @endif
            </div>
        </x-slot>

        @forelse($tasks as $task)
            @php $canToggleTask = $task->canBeCompletedBy(auth()->user()); @endphp
            <div class="mc-task-row" wire:key="project-task-{{ $task->id }}">
                @if($canToggleTask)
                    <button type="button" wire:click="toggleTask({{ $task->id }})" class="mc-task-check {{ $task->isCompleted() ? 'is-done' : '' }}" aria-label="{{ $task->isCompleted() ? 'Reopen' : 'Complete' }} {{ $task->title }}">
                        @if($task->isCompleted())<x-filament::icon icon="heroicon-m-check" style="width:.75rem;height:.75rem;" />@endif
                    </button>
                @else
                    <span class="mc-task-check {{ $task->isCompleted() ? 'is-done' : '' }}">
                        @if($task->isCompleted())<x-filament::icon icon="heroicon-m-check" style="width:.75rem;height:.75rem;" />@endif
                    </span>
                @endif

                <div style="min-width:0;flex:1;">
                    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:620;{{ $task->isCompleted() ? 'text-decoration:line-through;opacity:.65;' : '' }}">{{ $task->title }}</p>
                        @if($task->priority === 'high')<x-filament::badge color="danger" size="sm">High</x-filament::badge>@endif
                        @if($task->isOverdue())<x-filament::badge color="danger" size="sm">Overdue</x-filament::badge>@endif
                    </div>
                    @if($task->description)
                        <p class="mc-overview-muted" style="font-size:.72rem;line-height:1.45;margin-top:.2rem;">{{ $task->description }}</p>
                    @endif
                    <div class="mc-overview-muted" style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;font-size:.68rem;margin-top:.35rem;">
                        <span>{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                        <span>{{ $task->due_date ? 'Due '.$task->due_date->format('d M Y') : 'No deadline' }}</span>
                        @if($task->isCompleted() && $task->completed_at)<span>Completed {{ $task->completed_at->diffForHumans() }}</span>@endif
                    </div>
                </div>

                @if($canManage)
                    <x-filament::dropdown placement="bottom-end">
                        <x-slot name="trigger"><x-filament::icon-button icon="heroicon-m-ellipsis-vertical" color="gray" size="sm" label="Task actions" /></x-slot>
                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item wire:click="openTaskEdit({{ $task->id }})" icon="heroicon-m-pencil-square">Edit</x-filament::dropdown.list.item>
                            <x-filament::dropdown.list.item wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" icon="heroicon-m-trash" color="danger">Delete</x-filament::dropdown.list.item>
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                @endif
            </div>
        @empty
            <div style="padding:1.2rem 0;text-align:center;">
                <x-filament::icon icon="heroicon-o-check-circle" class="mx-auto h-7 w-7 text-gray-400" />
                <p class="mc-overview-muted" style="font-size:.76rem;margin-top:.4rem;">{{ $this->taskFilter === 'open' ? 'No open tasks.' : 'No tasks in this view.' }}</p>
            </div>
        @endforelse
    </x-filament::section>

    <x-filament::section style="margin-top:1rem;">
        <x-slot name="heading">Recent activity</x-slot>
        <x-slot name="description">The latest recorded changes across this project.</x-slot>

        @forelse($activity as $entry)
            <div class="mc-activity-row">
                <span class="mc-activity-icon" style="--activity-color:{{ $entry->color() }};">
                    <x-filament::icon :icon="$entry->icon()" style="width:.9rem;height:.9rem;" />
                </span>
                <div style="min-width:0;padding-top:.15rem;">
                    <p class="text-gray-950 dark:text-white" style="font-size:.78rem;line-height:1.4;">
                        <strong>{{ $entry->user?->name ?? 'System' }}</strong> {{ $entry->description }}
                    </p>
                    <p class="mc-overview-muted" title="{{ $entry->created_at->format('d M Y, H:i:s') }}" style="font-size:.68rem;margin-top:.15rem;">{{ $entry->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div style="padding:1rem 0;text-align:center;">
                <x-filament::icon icon="heroicon-o-clock" class="mx-auto h-7 w-7 text-gray-400" />
                <p class="mc-overview-muted" style="font-size:.76rem;margin-top:.4rem;">New changes will appear here automatically.</p>
            </div>
        @endforelse
    </x-filament::section>

    @if($showTransitionReadinessModal)
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="closeTransitionReadinessModal">
            <div class="mc-modal-panel mc-modal-panel-wide">
                <div class="mc-modal-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                        <div>
                            <h3 class="mc-modal-heading">Readiness warning before status change</h3>
                            <p class="mc-modal-description">
                                You are moving this project from {{ $pendingTransitionSummary['current_label'] ?? 'current status' }} to {{ $pendingTransitionSummary['target_label'] ?? 'the next status' }} while readiness issues are still open.
                            </p>
                        </div>
                        <button type="button" class="mc-iconbtn" wire:click="closeTransitionReadinessModal">✕</button>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem;margin-bottom:1rem;">
                        <div class="mc-readiness-group">
                            <p class="mc-overview-muted" style="font-size:.58rem;text-transform:uppercase;font-weight:800;">Readiness</p>
                            <p class="text-gray-950 dark:text-white" style="font-size:1.25rem;font-weight:850;margin-top:.18rem;">{{ $pendingTransitionSummary['score'] ?? 0 }}%</p>
                        </div>
                        <div class="mc-readiness-group">
                            <p class="mc-overview-muted" style="font-size:.58rem;text-transform:uppercase;font-weight:800;">Critical</p>
                            <p style="font-size:1.25rem;font-weight:850;margin-top:.18rem;color:{{ ($pendingTransitionSummary['critical'] ?? 0) > 0 ? '#dc2626' : '#059669' }};">{{ $pendingTransitionSummary['critical'] ?? 0 }}</p>
                        </div>
                        <div class="mc-readiness-group">
                            <p class="mc-overview-muted" style="font-size:.58rem;text-transform:uppercase;font-weight:800;">Warnings</p>
                            <p style="font-size:1.25rem;font-weight:850;margin-top:.18rem;color:{{ ($pendingTransitionSummary['warning'] ?? 0) > 0 ? '#d97706' : '#059669' }};">{{ $pendingTransitionSummary['warning'] ?? 0 }}</p>
                        </div>
                    </div>

                    <div style="display:grid;gap:.5rem;max-height:320px;overflow:auto;padding-right:.15rem;">
                        @foreach($pendingTransitionIssues as $issue)
                            @php
                                $issueColor = $issue['severity'] === 'critical' ? '#dc2626' : '#d97706';
                                $issueBg = $issue['severity'] === 'critical' ? 'rgba(239,68,68,.1)' : 'rgba(245,158,11,.12)';
                            @endphp
                            <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.68rem .75rem;border:1px solid rgba(148,163,184,.2);border-radius:.7rem;background:rgba(148,163,184,.04);">
                                <span style="width:21px;height:21px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;flex:none;background:{{ $issueBg }};color:{{ $issueColor }};font-size:.65rem;font-weight:850;">{{ $issue['severity'] === 'critical' ? '!' : '•' }}</span>
                                <span style="min-width:0;">
                                    <span class="text-gray-950 dark:text-white" style="display:block;font-size:.75rem;font-weight:800;line-height:1.3;">{{ $issue['label'] }}</span>
                                    <span class="mc-overview-muted" style="display:block;font-size:.68rem;line-height:1.45;margin-top:.12rem;">{{ $issue['detail'] }}</span>
                                    <span class="mc-overview-muted" style="display:block;font-size:.6rem;text-transform:uppercase;font-weight:800;letter-spacing:.05em;margin-top:.28rem;">{{ $issue['group'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top:.9rem;padding:.7rem .8rem;border-radius:.7rem;background:rgba(245,158,11,.1);color:#92400e;font-size:.72rem;line-height:1.45;">
                        This does not block the status change. It exists to prevent accidental lifecycle moves when important project data is incomplete.
                    </div>

                    <div class="mc-modal-actions">
                        <x-filament::button wire:click="closeTransitionReadinessModal" color="gray" size="sm">Cancel</x-filament::button>
                        <x-filament::button wire:click="confirmPendingTransition" color="warning" size="sm">Continue anyway</x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showTaskModal)
        <div class="mc-modal-backdrop" wire:click.self="$set('showTaskModal', false)">
            <div class="mc-modal-panel">
                <div class="mc-modal-body">
                    <h3 class="mc-modal-heading">{{ $editingTaskId ? 'Edit task' : 'Add project task' }}</h3>
                    <p class="mc-modal-description">Keep the title actionable and assign a deadline only when it is meaningful.</p>

                    <label for="task-title" class="mc-overview-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;margin-bottom:.35rem;">Title</label>
                    <input id="task-title" type="text" wire:model="taskTitle" class="mc-task-field text-gray-950 dark:text-white" placeholder="e.g. Collect partner mandates">
                    @error('taskTitle')<p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p>@enderror

                    <label for="task-description" class="mc-overview-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;margin:1rem 0 .35rem;">Notes</label>
                    <textarea id="task-description" wire:model="taskDescription" rows="3" class="mc-task-field text-gray-950 dark:text-white" placeholder="Optional context or expected result"></textarea>
                    @error('taskDescription')<p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p>@enderror

                    <div class="mc-task-form-grid" style="margin-top:1rem;">
                        <div>
                            <label for="task-assignee" class="mc-overview-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;margin-bottom:.35rem;">Assignee</label>
                            <select id="task-assignee" wire:model="taskAssignedTo" class="mc-task-field text-gray-950 dark:text-white">
                                <option value="">Unassigned</option>
                                @foreach($taskAssignees as $assignee)<option value="{{ $assignee->id }}">{{ $assignee->name }}</option>@endforeach
                            </select>
                            @error('taskAssignedTo')<p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="task-due" class="mc-overview-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;margin-bottom:.35rem;">Deadline</label>
                            <input id="task-due" type="date" wire:model="taskDueDate" class="mc-task-field text-gray-950 dark:text-white">
                            @error('taskDueDate')<p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label for="task-priority" class="mc-overview-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;margin:1rem 0 .35rem;">Priority</label>
                    <select id="task-priority" wire:model="taskPriority" class="mc-task-field text-gray-950 dark:text-white">
                        @foreach(\App\Models\ProjectTask::PRIORITIES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>

                    <div class="mc-modal-actions">
                        <x-filament::button wire:click="$set('showTaskModal', false)" color="gray" size="sm">Cancel</x-filament::button>
                        <x-filament::button wire:click="saveTask" wire:loading.attr="disabled" wire:target="saveTask" size="sm">{{ $editingTaskId ? 'Save changes' : 'Add task' }}</x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
