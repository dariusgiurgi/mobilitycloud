<x-filament-panels::page>
    <x-ui-polish />
    @php
        $categories = $this->archiveCategories();
        $selected = $this->selectedCount();
        $totalCategories = count($categories);
        $selectedRecords = collect($categories)
            ->filter(fn (array $category, string $key): bool => (bool) ($include[$key] ?? false))
            ->sum('count');
        $exportsLocked = $record->exportsLockedUntilPayment();
        $canConfigure = $this->canConfigureArchive();
        $recommendations = $this->finalisationRecommendations();
        $groups = [
            'Project essentials' => ['project_data', 'application', 'participants', 'budget', 'agreements'],
            'Evidence & files' => ['generated_records', 'project_files', 'mobility', 'dissemination'],
        ];
    @endphp

    <style>
        .mc-final-hero { padding:1.1rem 1.2rem;border:1px solid rgba(99,102,241,.2);border-radius:1rem;background:linear-gradient(125deg,rgba(99,102,241,.11),rgba(14,165,233,.05)); }
        .mc-final-hero-main { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap; }
        .mc-final-eyebrow { color:#6366f1;font-size:.64rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase; }
        .mc-final-title { color:#18181b;font-size:1.15rem;font-weight:800;letter-spacing:-.02em;margin:.22rem 0 0; }
        .mc-final-copy { color:#64748b;font-size:.76rem;line-height:1.48;margin:.3rem 0 0;max-width:630px; }
        .mc-final-summary { display:flex;align-items:center;gap:.55rem;flex-wrap:wrap; }
        .mc-final-stat { min-width:92px;padding:.5rem .62rem;border:1px solid rgba(99,102,241,.18);border-radius:.7rem;background:rgba(255,255,255,.7); }
        .mc-final-stat strong { display:block;color:#18181b;font-size:.98rem;line-height:1; }
        .mc-final-stat span { display:block;color:#64748b;font-size:.61rem;font-weight:700;text-transform:uppercase;letter-spacing:.045em;margin-top:.25rem; }
        .mc-final-steps { display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.95rem;padding-top:.85rem;border-top:1px solid rgba(99,102,241,.15); }
        .mc-final-step { display:inline-flex;align-items:center;gap:.4rem;color:#64748b;font-size:.68rem;font-weight:650; }
        .mc-final-step i { width:18px;height:18px;border-radius:999px;display:inline-grid;place-items:center;background:rgba(99,102,241,.1);color:#4f46e5;font-style:normal;font-size:.61rem;font-weight:800; }
        .mc-final-layout { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem;margin-top:1rem; }
        .mc-final-group { padding:.85rem;border:1px solid rgba(148,163,184,.2);border-radius:.9rem;background:#fff; }
        .mc-final-group-head { display:flex;align-items:baseline;justify-content:space-between;gap:.6rem;margin:0 0 .65rem;padding:0 .15rem; }
        .mc-final-group-head h3 { color:#18181b;font-size:.84rem;font-weight:780;margin:0; }
        .mc-final-group-head span { color:#94a3b8;font-size:.65rem;font-weight:650; }
        .mc-final-list { display:flex;flex-direction:column;gap:.42rem; }
        .mc-final-row { width:100%;display:grid;grid-template-columns:22px minmax(0,1fr) auto;gap:.55rem;align-items:center;padding:.62rem .65rem;text-align:left;border:1px solid rgba(148,163,184,.18);border-radius:.68rem;background:transparent;transition:.15s border-color,.15s background,.15s transform; }
        .mc-final-row:not(:disabled) { cursor:pointer; }
        .mc-final-row:not(:disabled):hover { border-color:rgba(99,102,241,.5);background:rgba(99,102,241,.04);transform:translateY(-1px); }
        .mc-final-row.is-selected { border-color:rgba(79,70,229,.45);background:rgba(99,102,241,.065); }
        .mc-final-check { width:20px;height:20px;display:inline-grid;place-items:center;border-radius:999px;border:1px solid rgba(100,116,139,.32);color:transparent;font-size:.7rem;font-weight:800; }
        .mc-final-row.is-selected .mc-final-check { background:#4f46e5;border-color:#4f46e5;color:#fff; }
        .mc-final-row-title { color:#27272a;font-size:.74rem;font-weight:750;line-height:1.3; }
        .mc-final-row-copy { display:block;color:#7c8799;font-size:.64rem;line-height:1.35;margin-top:.12rem; }
        .mc-final-count { color:#64748b;font-size:.63rem;font-weight:700;white-space:nowrap; }
        .mc-final-note { display:flex;gap:.6rem;align-items:flex-start;padding:.75rem .85rem;margin-top:1rem;border:1px solid rgba(245,158,11,.25);border-radius:.8rem;background:rgba(255,251,235,.72); }
        .mc-final-preflight { margin-top:1rem;padding:.85rem;border:1px solid rgba(148,163,184,.2);border-radius:.9rem;background:rgba(248,250,252,.75); }
        .mc-final-preflight-head { display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;margin:0 0 .7rem; }
        .mc-final-preflight-head h3 { color:#27272a;font-size:.82rem;font-weight:780;margin:.14rem 0 0; }
        .mc-final-preflight-head p { color:#7c8799;font-size:.66rem;line-height:1.4;margin:.16rem 0 0; }
        .mc-final-preflight-note { color:#64748b;font-size:.62rem;font-weight:700;text-align:right;line-height:1.35;max-width:165px; }
        .mc-final-recommendations { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem; }
        .mc-final-recommendation { display:grid;grid-template-columns:18px minmax(0,1fr) auto;gap:.45rem;align-items:center;padding:.55rem .6rem;border:1px solid rgba(148,163,184,.2);border-radius:.65rem;background:#fff;text-decoration:none;transition:.15s border-color,.15s background; }
        .mc-final-recommendation:hover { border-color:rgba(99,102,241,.45);background:rgba(99,102,241,.04); }
        .mc-final-recommendation-dot { width:16px;height:16px;display:inline-grid;place-items:center;border-radius:999px;background:rgba(245,158,11,.13);color:#b45309;font-size:.64rem;font-weight:800; }
        .mc-final-recommendation.is-critical .mc-final-recommendation-dot { background:rgba(239,68,68,.1);color:#dc2626; }
        .mc-final-recommendation-title { color:#27272a;font-size:.7rem;font-weight:750;line-height:1.3; }
        .mc-final-recommendation-copy { display:block;color:#7c8799;font-size:.61rem;line-height:1.32;margin-top:.1rem; }
        .mc-final-recommendation-action { color:#4f46e5;font-size:.62rem;font-weight:750;white-space:nowrap; }
        .mc-final-ready { display:flex;align-items:center;gap:.5rem;color:#15803d;font-size:.68rem;font-weight:700;padding:.15rem 0; }
        .mc-final-ready i { width:19px;height:19px;display:inline-grid;place-items:center;border-radius:999px;background:rgba(34,197,94,.12);font-style:normal; }
        .dark .mc-final-title,.dark .mc-final-stat strong,.dark .mc-final-group-head h3,.dark .mc-final-row-title { color:#f4f4f5; }
        .dark .mc-final-stat,.dark .mc-final-group,.dark .mc-final-recommendation { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-final-preflight { background:rgba(17,24,39,.65);border-color:rgba(255,255,255,.1); }
        .dark .mc-final-preflight-head h3,.dark .mc-final-recommendation-title { color:#f4f4f5; }
        .dark .mc-final-row { border-color:rgba(255,255,255,.1); }
        .dark .mc-final-row.is-selected { background:rgba(99,102,241,.18); }
        @media (max-width:800px) { .mc-final-layout { grid-template-columns:1fr; } }
        @media (max-width:650px) { .mc-final-recommendations { grid-template-columns:1fr; } }
        @media (max-width:520px) { .mc-final-summary { width:100%; }.mc-final-stat { flex:1; }.mc-final-row { grid-template-columns:22px minmax(0,1fr); }.mc-final-count { grid-column:2; }.mc-final-preflight-head { display:block; }.mc-final-preflight-note { text-align:left;margin-top:.35rem;max-width:none; } }
    </style>

    <section class="mc-final-hero">
        <div class="mc-final-hero-main">
            <div>
                <div class="mc-final-eyebrow">Finalisation</div>
                <h2 class="mc-final-title">Prepare the final project archive</h2>
                <p class="mc-final-copy">Choose what the handover ZIP should contain. The selection is saved automatically and never removes anything from the project.</p>
            </div>
            <div class="mc-final-summary">
                <div class="mc-final-stat"><strong>{{ $selected }}/{{ $totalCategories }}</strong><span>categories</span></div>
                <div class="mc-final-stat"><strong>{{ $selectedRecords }}</strong><span>records included</span></div>
                @if ($exportsLocked)
                    <x-filament::badge color="warning" icon="heroicon-m-lock-closed">Export locked</x-filament::badge>
                @elseif ($selected)
                    <x-filament::button tag="a" :href="route('projects.final-archive', $record)" icon="heroicon-m-archive-box-arrow-down">Download ZIP</x-filament::button>
                @endif
            </div>
        </div>
        <div class="mc-final-steps">
            <span class="mc-final-step"><i>1</i> Select project material</span>
            <span class="mc-final-step"><i>2</i> Download the handover ZIP</span>
            <span class="mc-final-step"><i>3</i> Keep the original project data intact</span>
        </div>
    </section>

    @if ($exportsLocked)
        <div class="mc-final-note">
            <x-filament::icon icon="heroicon-o-lock-closed" style="width:1.05rem;height:1.05rem;color:#d97706;flex:none;margin-top:.05rem;" />
            <div>
                <strong class="text-gray-950 dark:text-white" style="font-size:.76rem;">Archive download unlocks after payment confirmation</strong>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;line-height:1.42;margin:.12rem 0 0;">You can review or prepare the selection now. The ZIP becomes available when the invoice is marked as paid.</p>
            </div>
        </div>
    @endif

    <section class="mc-final-preflight">
        <div class="mc-final-preflight-head">
            <div>
                <div class="mc-final-eyebrow">Recommended before handover</div>
                <h3>{{ count($recommendations) ? count($recommendations).' item'.(count($recommendations) === 1 ? '' : 's').' worth checking' : 'The project looks ready to hand over' }}</h3>
                <p>These are helpful final checks, not requirements for downloading the archive.</p>
            </div>
            <span class="mc-final-preflight-note">You can export the ZIP at any time.</span>
        </div>

        @if (count($recommendations))
            <div class="mc-final-recommendations">
                @foreach ($recommendations as $recommendation)
                    <a href="{{ $recommendation['url'] }}" class="mc-final-recommendation {{ $recommendation['severity'] === 'critical' ? 'is-critical' : '' }}">
                        <span class="mc-final-recommendation-dot">!</span>
                        <span>
                            <span class="mc-final-recommendation-title">{{ $recommendation['label'] }}</span>
                            <span class="mc-final-recommendation-copy">{{ $recommendation['detail'] }}</span>
                        </span>
                        <span class="mc-final-recommendation-action">{{ $recommendation['action'] }} →</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="mc-final-ready"><i>✓</i> Key final checks are complete. You can still update the archive selection whenever you need.</div>
        @endif
    </section>

    <div class="mc-final-layout">
        @foreach ($groups as $groupLabel => $keys)
            @php($groupCategories = collect($keys)->mapWithKeys(fn (string $key): array => [$key => $categories[$key]])->all())
            <section class="mc-final-group">
                <div class="mc-final-group-head">
                    <h3>{{ $groupLabel }}</h3>
                    <span>{{ count($groupCategories) }} categories</span>
                </div>
                <div class="mc-final-list">
                    @foreach ($groupCategories as $key => $category)
                        @php($isSelected = (bool) ($include[$key] ?? false))
                        <button type="button" class="mc-final-row {{ $isSelected ? 'is-selected' : '' }}" @if ($canConfigure) wire:click="toggleArchiveCategory('{{ $key }}')" @else disabled @endif>
                            <span class="mc-final-check">✓</span>
                            <span>
                                <span class="mc-final-row-title">{{ $category['label'] }}</span>
                                <span class="mc-final-row-copy">{{ $category['detail'] }}</span>
                            </span>
                            <span class="mc-final-count">{{ $category['count'] }} {{ $category['count'] === 1 ? 'record' : 'records' }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    @if (! $canConfigure)
        <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.75rem;">Only the project owner and editors can change the archive selection.</p>
    @elseif (! $selected)
        <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.75rem;">Select at least one category to enable the final ZIP download.</p>
    @endif
</x-filament-panels::page>
