<x-filament-panels::page>
    @php
        $projects = $this->getProjects();
        $user = auth()->user();
        $canCreate = $user?->can('create', \App\Models\Project::class) ?? false;
        $requiresBillingDetails = $user && ! $user->isUnlimitedAccount() && ! $user->hasBillingDetails();
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

    @if (! $archived && $requiresBillingDetails)
        <x-filament::section style="margin-bottom:1rem;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:flex-start;gap:.85rem;max-width:780px;">
                    <span style="width:42px;height:42px;display:inline-flex;align-items:center;justify-content:center;flex:none;border-radius:.75rem;background:rgba(245,158,11,.12);color:#d97706;">
                        <x-filament::icon icon="heroicon-o-identification" style="width:1.25rem;height:1.25rem;" />
                    </span>
                    <div>
                        <p style="font-size:.67rem;font-weight:750;text-transform:uppercase;letter-spacing:.06em;color:#d97706;">Billing details required</p>
                        <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;margin-top:.15rem;">Complete your billing profile before creating a project</h2>
                        <p class="mc-project-muted" style="font-size:.82rem;line-height:1.55;margin-top:.3rem;">
                            To create projects on your own account, add the legal billing name, country and billing address in your profile. We need these details before project creation because approved projects generate a manual fiscal invoice.
                        </p>
                    </div>
                </div>
                <x-filament::button tag="a" :href="\App\Filament\Pages\AccountSettings::getUrl()" icon="heroicon-o-arrow-right" icon-position="after">
                    Complete billing details
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    @if ($projects->isEmpty())
        <x-filament::section wire:poll.5s>
            <div style="padding:2.75rem 1rem;text-align:center;max-width:460px;margin:0 auto;">
                <span style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:.8rem;background:rgba(99,102,241,.1);color:#6366f1;margin-bottom:.9rem;">
                    <x-filament::icon icon="heroicon-o-rectangle-stack" style="width:1.5rem;height:1.5rem;" />
                </span>
                <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;">{{ $archived ? 'No archived projects' : ($canCreate ? 'Create your first project' : ($requiresBillingDetails ? 'Complete billing details to create projects' : 'No projects available')) }}</h2>
                <p class="mc-project-muted" style="font-size:.83rem;line-height:1.55;margin:.35rem 0 1rem;">{{ $archived ? 'Archived projects are kept here for restoration by the account owner.' : ($canCreate ? 'Keep the application, budget, participants and documents together from the beginning.' : ($requiresBillingDetails ? 'You can still accept invitations from other project owners, but creating your own project requires billing details first.' : 'Ask the project owner to create a project or invite you to an existing one.')) }}</p>
                @if($archived)
                    <x-filament::button wire:click="$set('archived', false)" color="gray" icon="heroicon-o-arrow-left">Back to active projects</x-filament::button>
                @elseif($canCreate)
                    <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::accountUrl('create')" icon="heroicon-o-plus">New project</x-filament::button>
                @endif
            </div>
        </x-filament::section>
    @else
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.2rem;">
            <div style="display:flex;align-items:center;gap:.35rem;">
                <p class="mc-project-muted" style="font-size:.8rem;">{{ $projects->count() }} {{ str('project')->plural($projects->count()) }} · {{ $archived ? 'archived projects remain restorable' : 'current work is shown first' }}</p>
                @unless($archived)
                    <x-help-tip id="project-card-order" title="Project order and progress">
                        Active and approved projects appear first, followed by applications and completed work. Each progress bar compares recorded expenses with the approved grant, or with the requested budget before approval.
                    </x-help-tip>
                @endunless
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
                    $ownerLabel = $project->ownerLabelFor(auth()->user());
                    $accessLabel = $project->accessLabelFor(auth()->user());
                @endphp

                @if($archived)<div class="mc-project-list-card">@else<a href="{{ $this->getProjectUrl($project) }}" class="mc-project-list-card">@endif
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;">
                        <div style="min-width:0;">
                            <div style="display:flex;align-items:center;gap:.35rem;">
                                <h2 class="text-gray-950 dark:text-white" style="font-size:.96rem;font-weight:650;line-height:1.35;overflow-wrap:anywhere;">{{ $project->name }}</h2>
                                @if($project->access_mode === 'restricted')
                                    <x-filament::icon icon="heroicon-o-lock-closed" class="text-gray-400" style="width:.85rem;height:.85rem;flex:none;" title="Restricted project access" />
                                @endif
                            </div>
                            @if ($project->acronym || $project->grant_ref)
                                <p class="mc-project-muted" style="font-size:.7rem;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ collect([$project->acronym, $project->grant_ref])->filter()->join(' · ') }}
                                </p>
                            @endif
                            <p class="mc-project-muted" style="font-size:.7rem;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $ownerLabel ?: 'Your project' }} · {{ $accessLabel }}
                            </p>
                        </div>
                        <div style="display:flex;align-items:flex-end;gap:.35rem;flex-direction:column;">
                            <x-filament::badge :color="$archived ? 'gray' : $status->getColor()" size="sm">{{ $archived ? 'Archived' : $status->getLabel() }}</x-filament::badge>
                            @if($ownerLabel)
                                <x-filament::badge color="gray" size="sm">{{ $accessLabel }}</x-filament::badge>
                            @endif
                        </div>
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

                    @if($archived)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:auto;padding-top:1rem;">
                            <span class="mc-project-muted" style="font-size:.7rem;">Archived {{ $project->deleted_at?->diffForHumans() }}</span>
                            @if($project->canManageLifecycleBy(auth()->user()))
                                <x-filament::button wire:click="restoreProject({{ $project->id }})" wire:confirm="Restore {{ $project->name }} to active projects?" color="gray" size="sm" icon="heroicon-o-arrow-uturn-left">Restore</x-filament::button>
                            @endif
                        </div>
                    @else
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.25rem;margin-top:auto;padding-top:1rem;color:#6366f1;font-size:.76rem;font-weight:600;">
                            Open project
                            <x-filament::icon icon="heroicon-m-arrow-right" style="width:.9rem;height:.9rem;" />
                        </div>
                    @endif
                @if($archived)</div>@else</a>@endif
            @endforeach
        </div>
    @endif

    {{-- This ListRecords page replaces Filament's table with cards, so it must
         render the action modal host explicitly. --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
