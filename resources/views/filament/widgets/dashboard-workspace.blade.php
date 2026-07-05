<x-filament-widgets::widget>
    <style>
        .mc-dashboard-grid { display:grid; grid-template-columns:minmax(0,1.55fr) minmax(280px,.85fr); gap:1rem; align-items:start; }
        .mc-dashboard-stack { display:grid; gap:1rem; }
        .mc-project-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
        .mc-project-card { display:block; padding:1rem; border:1px solid rgba(148,163,184,.24); border-radius:.75rem; text-decoration:none; transition:border-color .15s,box-shadow .15s,transform .15s; }
        .mc-project-card:hover { border-color:rgba(99,102,241,.5); box-shadow:0 8px 24px rgba(15,23,42,.07); transform:translateY(-1px); }
        .mc-row-link { display:flex; align-items:flex-start; gap:.75rem; padding:.8rem 0; text-decoration:none; }
        .mc-row-link + .mc-row-link { border-top:1px solid rgba(148,163,184,.2); }
        .mc-action-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; }
        .mc-action { display:flex; align-items:center; gap:.65rem; min-height:64px; padding:.75rem; border:1px solid rgba(148,163,184,.24); border-radius:.7rem; text-decoration:none; transition:background .15s,border-color .15s; }
        .mc-action:hover { background:rgba(99,102,241,.05); border-color:rgba(99,102,241,.45); }
        .mc-muted { color:#64748b; }
        .dark .mc-muted { color:#94a3b8; }
        .dark .mc-project-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.2); }
        @media (max-width:1024px) { .mc-dashboard-grid { grid-template-columns:1fr; } }
        @media (max-width:640px) { .mc-project-grid,.mc-action-grid { grid-template-columns:1fr; } }
    </style>

    <div class="mc-dashboard-grid">
        <div class="mc-dashboard-stack">
            @if ($pendingInvitations->isNotEmpty())
                <x-filament::section>
                    <x-slot name="heading">Pending invitations</x-slot>
                    <x-slot name="description">Project invitations sent to your email address.</x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::badge color="info">{{ $pendingInvitations->count() }}</x-filament::badge>
                    </x-slot>

                    @foreach ($pendingInvitations as $invitation)
                        <a href="{{ route('workspace-invitations.accept', $invitation->token) }}" class="mc-row-link">
                            <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;flex:none;border-radius:.65rem;background:rgba(99,102,241,.1);color:#6366f1;">
                                <x-filament::icon icon="heroicon-o-envelope-open" style="width:1rem;height:1rem;" />
                            </span>
                            <span style="min-width:0;flex:1;">
                                <span class="text-gray-950 dark:text-white" style="display:block;font-size:.875rem;font-weight:600;line-height:1.35;">{{ $invitation->project?->name ?? 'Project invitation' }}</span>
                                <span class="mc-muted" style="display:block;font-size:.76rem;margin-top:.15rem;">
                                    {{ $invitation->workspace?->name ?? 'MobilityCloud' }} · expires {{ $invitation->expires_at?->format('d M Y') }}
                                </span>
                            </span>
                            <span style="font-size:.76rem;font-weight:700;color:#6366f1;">Accept</span>
                        </a>
                    @endforeach
                </x-filament::section>
            @endif

            <x-filament::section>
                <x-slot name="heading">
                    <span style="display:inline-flex;align-items:center;gap:.35rem;">
                        Needs attention
                        <x-help-tip id="dashboard-attention" title="How priorities are detected">
                            MobilityCloud combines the project readiness check with urgent dates, participant files, expense evidence, signed records and open tasks. This is an operational reminder, not a compliance decision.
                        </x-help-tip>
                    </span>
                </x-slot>
                <x-slot name="description">Priorities detected from your current project data.</x-slot>
                @if ($attentionCount > 0)
                    <x-slot name="headerEnd">
                        <x-filament::badge color="warning">{{ $attentionCount }}</x-filament::badge>
                    </x-slot>
                @endif

                @forelse ($attention as $item)
                    <a href="{{ $item['url'] }}" class="mc-row-link">
                        <span style="width:9px;height:9px;border-radius:9999px;margin-top:.35rem;flex:none;background:{{ $item['severity'] === 'danger' ? '#ef4444' : ($item['severity'] === 'warning' ? '#f59e0b' : '#3b82f6') }};"></span>
                        <span style="min-width:0;flex:1;">
                            <span class="text-gray-950 dark:text-white" style="display:block;font-size:.875rem;font-weight:600;line-height:1.35;">{{ $item['title'] }}</span>
                            <span class="mc-muted" style="display:block;font-size:.76rem;margin-top:.15rem;">{{ $item['project'] }} · {{ $item['detail'] }}</span>
                        </span>
                        <x-filament::icon icon="heroicon-m-chevron-right" class="mc-muted" style="width:1rem;height:1rem;flex:none;margin-top:.1rem;" />
                    </a>
                @empty
                    <div style="padding:1.4rem 0;text-align:center;">
                        <x-filament::icon icon="heroicon-o-check-circle" style="width:2rem;height:2rem;color:#22c55e;margin:0 auto .5rem;" />
                        <p class="text-gray-950 dark:text-white" style="font-size:.875rem;font-weight:600;">Everything looks in order</p>
                        <p class="mc-muted" style="font-size:.78rem;margin-top:.2rem;">No urgent project issues were detected.</p>
                    </div>
                @endforelse

                @if ($attentionCount > $attention->count())
                    <p class="mc-muted" style="font-size:.75rem;margin-top:.65rem;">+ {{ $attentionCount - $attention->count() }} more items available inside the projects.</p>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <span style="display:inline-flex;align-items:center;gap:.35rem;">
                        Current projects
                        <x-help-tip id="dashboard-project-progress" title="Financial progress">
                            The progress bar compares recorded expenses with the approved grant. Before approval, it uses the requested budget instead.
                        </x-help-tip>
                    </span>
                </x-slot>
                <x-slot name="description">Continue where work is most relevant.</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button tag="a" :href="$projectsUrl" color="gray" size="sm">View all</x-filament::button>
                </x-slot>

                @if ($projects->isEmpty())
                    <div style="padding:1.5rem 0;text-align:center;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.875rem;font-weight:600;">No current projects</p>
                        <p class="mc-muted" style="font-size:.78rem;margin-top:.2rem;">Create a project to start planning your work.</p>
                    </div>
                @else
                    <div class="mc-project-grid">
                        @foreach ($projects as $project)
                            @php
                                $status = $project->statusEnum();
                                $budget = $project->effective_budget;
                                $remaining = $project->remaining;
                                $date = $project->mobility_start_date ?? $project->start_date;
                                $projectReadiness = $readiness->get($project->id);
                            @endphp
                            <a href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('overview', ['record' => $project], tenant: $project->workspace) }}" class="mc-project-card">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;">
                                    <div style="min-width:0;">
                                        <p class="text-gray-950 dark:text-white" style="font-size:.9rem;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $project->name }}</p>
                                        <p class="mc-muted" style="font-size:.72rem;margin-top:.15rem;">{{ $date?->format('d M Y') ?? 'Dates not set' }} · {{ $project->participants->count() }} participants</p>
                                    </div>
                                    <x-filament::badge :color="$status->getColor()" size="sm">{{ $status->getLabel() }}</x-filament::badge>
                                </div>

                                @if ($projectReadiness)
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:.85rem;">
                                        <span class="mc-muted" style="font-size:.72rem;">Readiness</span>
                                        <span class="text-gray-950 dark:text-white" style="font-size:.72rem;font-weight:650;">{{ $projectReadiness['score'] }}%</span>
                                    </div>
                                    <div style="height:5px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:.3rem;">
                                        <div style="height:100%;width:{{ $projectReadiness['score'] }}%;border-radius:9999px;background:{{ $projectReadiness['tone'] === 'success' ? '#22c55e' : ($projectReadiness['tone'] === 'warning' ? '#f59e0b' : '#ef4444') }};"></div>
                                    </div>
                                @endif

                                <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:1rem;font-size:.75rem;">
                                    <span class="mc-muted">Spent {{ number_format($project->spent, 0) }} €</span>
                                    <span class="text-gray-950 dark:text-white" style="font-weight:600;">{{ number_format($remaining, 0) }} € left</span>
                                </div>
                                <div style="height:6px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:.4rem;">
                                    <div style="height:100%;width:{{ min(100, $project->progress) }}%;border-radius:9999px;background:{{ $project->progress >= 100 ? '#ef4444' : '#6366f1' }};"></div>
                                </div>
                                <div class="mc-muted" style="display:flex;justify-content:space-between;font-size:.68rem;margin-top:.35rem;">
                                    <span>{{ number_format($budget, 0) }} € funding</span>
                                    <span>{{ $project->progress }}%</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if ($projectCount > $projects->count())
                        <p class="mc-muted" style="font-size:.75rem;margin-top:.8rem;">Showing the {{ $projects->count() }} projects that are most relevant right now.</p>
                    @endif
                @endif
            </x-filament::section>
        </div>

        <div class="mc-dashboard-stack">
            <x-filament::section>
                <x-slot name="heading">Upcoming milestones</x-slot>
                <x-slot name="description">The next 60 days.</x-slot>
                @if ($milestoneCount > 0)
                    <x-slot name="headerEnd">
                        <x-filament::badge color="info">{{ $milestoneCount }}</x-filament::badge>
                    </x-slot>
                @endif

                @forelse ($milestones as $milestone)
                    <a href="{{ $milestone['url'] }}" class="mc-row-link">
                        <span style="width:42px;flex:none;text-align:center;border-radius:.55rem;background:rgba(99,102,241,.1);padding:.4rem .2rem;color:#6366f1;">
                            <span style="display:block;font-size:.65rem;font-weight:700;text-transform:uppercase;line-height:1;">{{ $milestone['date']->format('M') }}</span>
                            <span style="display:block;font-size:1rem;font-weight:700;line-height:1.2;margin-top:.15rem;">{{ $milestone['date']->format('d') }}</span>
                        </span>
                        <span style="min-width:0;">
                            <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:600;">{{ $milestone['label'] }}</span>
                            <span class="mc-muted" style="display:block;font-size:.73rem;margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $milestone['project'] }}</span>
                        </span>
                    </a>
                @empty
                    <div style="padding:1.25rem 0;text-align:center;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.84rem;font-weight:600;">No milestones coming up</p>
                        <p class="mc-muted" style="font-size:.76rem;margin-top:.2rem;">Project and mobility dates will appear here.</p>
                    </div>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Quick actions</x-slot>
                <x-slot name="description">Shortcuts adapted to your current projects.</x-slot>

                <div class="mc-action-grid">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="mc-action">
                            <span style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;flex:none;border-radius:.55rem;background:rgba(99,102,241,.1);color:#6366f1;">
                                <x-filament::icon :icon="$action['icon']" style="width:1.05rem;height:1.05rem;" />
                            </span>
                            <span style="min-width:0;">
                                <span class="text-gray-950 dark:text-white" style="display:block;font-size:.78rem;font-weight:600;line-height:1.2;">{{ $action['label'] }}</span>
                                <span class="mc-muted" style="display:block;font-size:.68rem;margin-top:.18rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $action['description'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-widgets::widget>
