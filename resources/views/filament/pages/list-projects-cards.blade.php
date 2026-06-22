<x-filament-panels::page>
    @php
        $projects = $this->getProjects();
    @endphp

    <style>
        .mc-projects-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1rem; }
        .mc-project-list-card { display:flex;flex-direction:column;min-height:245px;padding:1.15rem;text-decoration:none;border:1px solid rgba(148,163,184,.22);border-radius:.85rem;background:white;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:transform .15s,border-color .15s,box-shadow .15s; }
        .mc-project-list-card:hover { transform:translateY(-2px);border-color:rgba(99,102,241,.45);box-shadow:0 10px 28px rgba(15,23,42,.08); }
        .mc-project-muted { color:#64748b; }
        .dark .mc-project-list-card { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-project-muted { color:#94a3b8; }
        .dark .mc-project-list-card:hover { box-shadow:0 10px 28px rgba(0,0,0,.22); }
        @media (max-width:640px) { .mc-projects-grid { grid-template-columns:1fr; } }
    </style>

    @if ($projects->isEmpty())
        <x-filament::section>
            <div style="padding:2.75rem 1rem;text-align:center;max-width:460px;margin:0 auto;">
                <span style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:.8rem;background:rgba(99,102,241,.1);color:#6366f1;margin-bottom:.9rem;">
                    <x-filament::icon icon="heroicon-o-rectangle-stack" style="width:1.5rem;height:1.5rem;" />
                </span>
                <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;">Create your first project</h2>
                <p class="mc-project-muted" style="font-size:.83rem;line-height:1.55;margin:.35rem 0 1rem;">Keep the application, budget, participants and documents together from the beginning.</p>
                <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::getUrl('create')" icon="heroicon-o-plus">
                    New project
                </x-filament::button>
            </div>
        </x-filament::section>
    @else
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.2rem;">
            <div style="display:flex;align-items:center;gap:.35rem;">
                <p class="mc-project-muted" style="font-size:.8rem;">{{ $projects->count() }} {{ str('project')->plural($projects->count()) }} · current work is shown first</p>
                <x-help-tip id="project-card-order" title="Project order and progress">
                    Active and approved projects appear first, followed by applications and completed work. Each progress bar compares recorded expenses with the approved grant, or with the requested budget before approval.
                </x-help-tip>
            </div>
        </div>

        <div class="mc-projects-grid">
            @foreach ($projects as $project)
                @php
                    $status = $project->statusEnum();
                    $budget = $project->effective_budget;
                    $spent = $project->spent;
                    $remaining = $project->remaining;
                    $progress = $project->progress;
                    $start = $project->mobility_start_date ?? $project->start_date;
                    $end = $project->mobility_end_date ?? $project->end_date;
                @endphp

                <a href="{{ $this->getProjectUrl($project) }}" class="mc-project-list-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;">
                        <div style="min-width:0;">
                            <h2 class="text-gray-950 dark:text-white" style="font-size:.96rem;font-weight:650;line-height:1.35;overflow-wrap:anywhere;">{{ $project->name }}</h2>
                            @if ($project->acronym || $project->grant_ref)
                                <p class="mc-project-muted" style="font-size:.7rem;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ collect([$project->acronym, $project->grant_ref])->filter()->join(' · ') }}
                                </p>
                            @endif
                        </div>
                        <x-filament::badge :color="$status->getColor()" size="sm">{{ $status->getLabel() }}</x-filament::badge>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:1.15rem;padding:.8rem 0;border-top:1px solid rgba(148,163,184,.18);border-bottom:1px solid rgba(148,163,184,.18);">
                        <div>
                            <p class="mc-project-muted" style="font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;">Period</p>
                            <p class="text-gray-950 dark:text-white" style="font-size:.76rem;font-weight:550;margin-top:.18rem;">
                                @if ($start || $end)
                                    {{ $start?->format('d M Y') ?? '—' }} → {{ $end?->format('d M Y') ?? '—' }}
                                @else
                                    Not set
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="mc-project-muted" style="font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;">Participants</p>
                            <p class="text-gray-950 dark:text-white" style="font-size:.76rem;font-weight:550;margin-top:.18rem;">{{ $project->participants_count }}</p>
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.75rem;">
                            <span class="mc-project-muted" style="font-size:.73rem;">{{ $project->approved_budget > 0 ? 'Approved funding' : 'Requested budget' }}</span>
                            <span class="text-gray-950 dark:text-white" style="font-size:.84rem;font-weight:650;">{{ number_format($budget, 2) }} €</span>
                        </div>
                        <div style="height:6px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:.5rem;">
                            <div style="height:100%;width:{{ min(100, $progress) }}%;border-radius:9999px;background:{{ $progress >= 100 ? '#ef4444' : '#6366f1' }};"></div>
                        </div>
                        <div class="mc-project-muted" style="display:flex;justify-content:space-between;font-size:.68rem;margin-top:.35rem;">
                            <span>{{ number_format($spent, 2) }} € spent</span>
                            <span>{{ number_format($remaining, 2) }} € remaining</span>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:.25rem;margin-top:auto;padding-top:1rem;color:#6366f1;font-size:.76rem;font-weight:600;">
                        Open project
                        <x-filament::icon icon="heroicon-m-arrow-right" style="width:.9rem;height:.9rem;" />
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- This ListRecords page replaces Filament's table with cards, so it must
         render the action modal host explicitly. --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
