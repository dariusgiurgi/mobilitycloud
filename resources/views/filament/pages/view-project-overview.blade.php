<x-filament-panels::page>
    @php
        $status = $this->getStatusEnum();
        $transitions = $status->allowedTransitions();
        $application = $this->getApplicationSummary();
        $participants = $this->getParticipantSummary();
        $documents = $this->getDocumentSummary();
        $activity = $this->getRecentActivity();
        $nextStep = $this->getNextStep();
        $urls = $this->getModuleUrls();
        $partners = $this->record->partners;
        $approved = (float) $this->record->approved_budget;
        $requested = (float) $this->record->total_budget;
        $ka = $this->record->ka_action;
        $kaLabel = $ka ? (\App\Support\ApplicationTemplates::list()[$ka] ?? strtoupper($ka)) : null;
        $canManage = $this->record->canBeManagedBy(auth()->user());
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
        .dark .mc-overview-card { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-overview-card:hover { box-shadow:0 10px 28px rgba(0,0,0,.22); }
        .dark .mc-overview-muted { color:#94a3b8; }
        @media (max-width:1100px) { .mc-overview-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:700px) { .mc-overview-grid,.mc-overview-detail-grid { grid-template-columns:1fr; } }
    </style>

    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;">
                <span class="mc-overview-muted" style="font-size:.78rem;font-weight:600;">Project stage</span>
                <x-filament::badge :color="$status->getColor()" size="lg">{{ $status->getLabel() }}</x-filament::badge>
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
                            wire:click="transitionTo('{{ $next->value }}')"
                            wire:confirm="Move this project from {{ $status->getLabel() }} to {{ $next->getLabel() }}?"
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
                <span class="mc-overview-muted" style="font-size:.68rem;">{{ $this->record->isWritingStage() ? 'Estimate' : $this->record->progress.'% spent' }}</span>
            </div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:650;margin-top:.85rem;">Budget</h3>
            @if ($this->record->isWritingStage())
                <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $eur($this->record->effective_budget) }}</p>
                <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">Requested grant currently saved</p>
            @else
                <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:650;margin-top:.2rem;">{{ $eur($this->record->remaining) }}</p>
                <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">remaining from {{ $eur($this->record->effective_budget) }}</p>
            @endif
            <span style="font-size:.72rem;font-weight:600;color:#6366f1;margin-top:auto;padding-top:.7rem;">Open budget →</span>
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
            <p class="mc-overview-muted" style="font-size:.72rem;margin-top:.15rem;">{{ $participants['total'] > 0 ? $participants['complete'].' records have all required files' : 'No participants added yet' }}</p>
            <span style="font-size:.72rem;font-weight:600;color:#6366f1;margin-top:auto;padding-top:.7rem;">Open participants →</span>
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
                {{ $documents['checklist_applies'] ? $documents['complete'].' checklist items complete' : 'Project files and generated records' }}
            </p>
            <span style="font-size:.72rem;font-weight:600;color:#6366f1;margin-top:auto;padding-top:.7rem;">Open documents →</span>
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
</x-filament-panels::page>
