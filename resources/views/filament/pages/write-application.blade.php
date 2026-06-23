<x-filament-panels::page>
    <x-ui-polish />
    @php
        $sections = $this->getSections();
        $visibleSections = $this->getVisibleSections();
        $summary = $this->getApplicationSummary();
        $review = $this->getConsistencyReview();
        $quality = $this->getQualityReview();
        $canManage = $record->canBeManagedBy(auth()->user());
        $categories = $sections->groupBy(fn ($section) => $section->category ?: 'Custom sections');
    @endphp

    <style>
        .mc-wa textarea { width:100%; background:transparent; border:1px solid rgba(100,116,139,.25); border-radius:8px; padding:10px 12px; font-size:13px; line-height:1.6; resize:vertical; color:inherit; }
        .mc-wa input.mc-title { width:100%; background:transparent; border:none; font-size:15px; font-weight:600; color:inherit; padding:2px 0; }
        .mc-wa select option { background:#fff; }
        .dark .mc-wa select option { background:#27303f; }
        .mc-iconbtn { flex-shrink:0; width:28px; height:28px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:#9ca3af; display:inline-flex; align-items:center; justify-content:center; }
        .mc-iconbtn:disabled { opacity:.25; cursor:default; }
        .mc-libcard { border:1px solid rgba(100,116,139,.2); border-radius:10px; padding:.85rem 1rem; margin-bottom:.6rem; }
        .mc-libcard:hover { border-color:#6366f1; }
        .mc-wa-toolbar { display:flex;align-items:center;gap:.65rem;flex-wrap:wrap; }
        .mc-wa-layout { display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:1.25rem;align-items:start;margin-top:1rem; }
        .mc-wa-sidebar { position:sticky;top:1rem;display:grid;gap:.75rem;max-height:calc(100vh - 2rem);overflow:auto;padding-right:.25rem;scrollbar-gutter:stable; }
        .mc-wa-sidebar::-webkit-scrollbar { width:7px; }
        .mc-wa-sidebar::-webkit-scrollbar-thumb { background:rgba(148,163,184,.45);border-radius:999px; }
        .mc-wa-sidecard { padding:.9rem;border:1px solid rgba(148,163,184,.22);border-radius:.85rem;background:#fff; }
        .mc-wa-outline { padding:0; }
        .mc-wa-outline a { display:flex;align-items:flex-start;gap:.45rem;padding:.42rem .5rem;border-radius:.45rem;color:#64748b;text-decoration:none;font-size:.72rem;line-height:1.35; }
        .mc-wa-outline a:hover { color:#4f46e5;background:rgba(99,102,241,.07); }
        .mc-wa-outline-list { max-height:360px;overflow:auto; }
        .mc-wa-section { scroll-margin-top:1rem; }
        .mc-wa-card-actions { display:flex;align-items:center;gap:.1rem; }
        .mc-wa-progress { height:7px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden; }
        .mc-wa-guidance { margin:.15rem 0 .75rem;padding:.65rem .75rem;border-left:3px solid #818cf8;border-radius:.35rem;background:rgba(99,102,241,.07);font-size:.72rem;line-height:1.55;color:#64748b; }
        .mc-wa-filter { width:auto;padding:7px 10px;border:1px solid rgba(100,116,139,.25);border-radius:7px;background:transparent;font-size:12px;color:inherit; }
        .mc-wa-review { display:grid;grid-template-columns:150px minmax(0,1fr);gap:.65rem;margin-top:.65rem;padding-top:.65rem;border-top:1px solid rgba(148,163,184,.18); }
        .mc-wa-note { width:100%;padding:7px 9px;border:1px solid rgba(100,116,139,.22);border-radius:7px;background:transparent;font-size:11px;color:inherit; }
        .mc-template-manager { display:grid;grid-template-columns:280px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-template-card { width:100%;text-align:left;border:1px solid rgba(148,163,184,.22);border-radius:.75rem;background:transparent;padding:.75rem;cursor:pointer;color:inherit; }
        .mc-template-card:hover { border-color:#818cf8;background:rgba(99,102,241,.05); }
        .mc-template-card-active { border-color:#6366f1;background:rgba(99,102,241,.08); }
        .mc-template-stat-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin:.8rem 0; }
        .mc-template-stat { border:1px solid rgba(148,163,184,.18);border-radius:.65rem;padding:.7rem;background:rgba(148,163,184,.05); }
        .mc-template-question-list { max-height:250px;overflow:auto;border:1px solid rgba(148,163,184,.18);border-radius:.65rem;padding:.7rem; }
        .mc-review-panel { display:grid;grid-template-columns:180px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-review-score { border-radius:.9rem;padding:1rem;background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(14,165,233,.08));border:1px solid rgba(99,102,241,.18); }
        .mc-review-issues { display:grid;gap:.55rem; }
        .mc-review-issue { display:grid;grid-template-columns:88px minmax(0,1fr) auto;gap:.7rem;align-items:start;padding:.72rem .82rem;border:1px solid rgba(148,163,184,.18);border-radius:.7rem;background:rgba(148,163,184,.04); }
        .mc-review-issue-compact { grid-template-columns:1fr;gap:.35rem;padding:.65rem .7rem; }
        .mc-review-pill { display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:.18rem .5rem;font-size:.61rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em; }
        .mc-review-critical { color:#b91c1c;background:rgba(239,68,68,.11); }
        .mc-review-warning { color:#b45309;background:rgba(245,158,11,.12); }
        .mc-review-suggestion { color:#4f46e5;background:rgba(99,102,241,.1); }
        .mc-quality-grid { display:grid;grid-template-columns:220px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-quality-main { border-radius:.95rem;padding:1rem;background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(99,102,241,.08));border:1px solid rgba(16,185,129,.18); }
        .mc-quality-criteria { display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.55rem; }
        .mc-quality-card { border:1px solid rgba(148,163,184,.18);border-radius:.75rem;padding:.75rem;background:rgba(148,163,184,.04);min-width:0; }
        .mc-quality-card-compact { padding:.6rem; }
        .mc-quality-bar { height:6px;border-radius:999px;background:rgba(148,163,184,.2);overflow:hidden;margin:.45rem 0 .5rem; }
        .mc-quality-bar > span { display:block;height:100%;border-radius:999px;background:#10b981; }
        .mc-quality-card ul { margin:.45rem 0 0 1rem;padding:0;font-size:.66rem;line-height:1.45;color:#64748b; }
        .mc-review-detail-grid { display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-review-detail-list { display:grid;gap:.55rem;max-height:58vh;overflow:auto;padding-right:.25rem; }
        .mc-check-row { display:flex;gap:.55rem;align-items:flex-start;padding:.55rem .65rem;border:1px solid rgba(148,163,184,.16);border-radius:.6rem;background:rgba(148,163,184,.04);font-size:.7rem;line-height:1.45; }
        .dark .mc-wa-sidecard { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        @media (max-width:1200px) { .mc-quality-grid { grid-template-columns:1fr; }.mc-quality-criteria { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:1100px) { .mc-wa-layout { grid-template-columns:1fr; }.mc-wa-sidebar { position:static;grid-row:1;max-height:none;overflow:visible;padding-right:0; }.mc-wa-outline-list { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:none; }.mc-template-manager { grid-template-columns:1fr; }.mc-template-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }.mc-review-panel { grid-template-columns:1fr; }.mc-review-detail-grid { grid-template-columns:1fr; }.mc-review-detail-list { max-height:none; } }
        @media (max-width:650px) { .mc-wa-outline-list { grid-template-columns:1fr; }.mc-template-stat-grid { grid-template-columns:1fr; }.mc-review-issue { grid-template-columns:1fr; }.mc-quality-criteria { grid-template-columns:1fr; } }
    </style>

    <div class="mc-wa">

    <x-filament::section>
        <div class="mc-wa-toolbar">
            <div style="min-width:0;margin-right:auto;">
                <div style="display:flex;align-items:center;gap:.45rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">Application workspace</h2>
                    <x-help-tip id="application-autosave" title="Automatic saving">
                        Answers are saved automatically after you stop typing. Export creates a PDF snapshot of the current saved application.
                    </x-help-tip>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.18rem;">
                    @if($canManage)
                        <span wire:loading.remove wire:target="content,titles,reviewStatuses,internalNotes">Changes are saved automatically @if($lastSavedAt) · saved at {{ $lastSavedAt }} @endif</span>
                        <span wire:loading wire:target="content,titles" style="color:#6366f1;">Saving changes…</span>
                    @else
                        Read-only access
                    @endif
                </p>
            </div>

            @if($canManage)
                <select wire:model="selectedTemplate" class="text-gray-950 dark:text-white"
                        style="max-width:320px;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:12px;">
                    @foreach($this->getTemplates() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-filament::button wire:click="openTemplateDetails" color="gray" size="sm" icon="heroicon-o-squares-2x2">
                    Template manager
                </x-filament::button>
                <x-filament::button wire:click="$set('showVersions', true)" color="gray" size="sm" icon="heroicon-o-clock">Versions</x-filament::button>
            @endif
            <x-filament::button tag="a" :href="route('projects.export-application', $record)" target="_blank" icon="heroicon-o-arrow-down-tray" size="sm">
                Export PDF
            </x-filament::button>
        </div>
    </x-filament::section>

    <div class="mc-wa-layout">
        <main style="min-width:0;">

    @if($sections->isNotEmpty())
        <div style="display:flex;gap:.55rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;">
            <input class="mc-wa-filter" style="min-width:230px;flex:1;" wire:model.live.debounce.300ms="sectionSearch" placeholder="Search questions or answers…">
            <select class="mc-wa-filter" wire:model.live="sectionFilter">
                <option value="all">All questions</option>
                <option value="empty">Unanswered</option>
                <option value="over-limit">Over character limit</option>
                <option value="review">Needs review</option>
                <option value="ready">Ready</option>
            </select>
            <span class="text-gray-400" style="font-size:.7rem;">{{ $visibleSections->count() }} shown</span>
        </div>
    @endif

    @if($sections->isEmpty())
        <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;margin:0 0 1rem;">No sections yet. Load a template (KA1/KA2) or add a free section.</p>
            @if($canManage)
                <button type="button" wire:click="addSection" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">+ Add section</button>
            @endif
        </div>
    @else
        @php $currentCat = null; @endphp
        @forelse($visibleSections as $sec)
            @if($sec->category && $sec->category !== $currentCat)
                @php $currentCat = $sec->category; @endphp
                <p class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:1.5rem 0 .5rem;">{{ $currentCat }}</p>
            @endif

            @php
                $text  = $this->content[$sec->id] ?? (string) $sec->content;
                $count = mb_strlen(strip_tags($text));
                $trim  = trim(strip_tags($text));
                $words = $trim === '' ? 0 : count(preg_split('/\s+/', $trim));
                $limit = $sec->char_limit;
                $over  = $limit && $count > $limit;
            @endphp

            @php $guidance = $this->getQuestionGuidance($sec); @endphp

            <div id="application-section-{{ $sec->id }}" wire:key="section-{{ $sec->id }}" class="mc-wa-section fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.1rem 1.25rem;margin-bottom:1rem;">
                <div style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.6rem;">
                    <input type="text" wire:key="title-{{ $sec->id }}" class="mc-title text-gray-950 dark:text-white"
                           wire:model.blur="titles.{{ $sec->id }}" @readonly(!$canManage)>

                    @if($canManage)
                    <div class="mc-wa-card-actions">
                    {{-- Move up --}}
                    <button type="button" wire:click="moveSection({{ $sec->id }}, -1)" title="Move up"
                            class="mc-iconbtn" @if($loop->first) disabled @endif
                            onmouseover="if(!this.disabled){this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';}"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"></path></svg>
                    </button>

                    {{-- Move down --}}
                    <button type="button" wire:click="moveSection({{ $sec->id }}, 1)" title="Move down"
                            class="mc-iconbtn" @if($loop->last) disabled @endif
                            onmouseover="if(!this.disabled){this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';}"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                    </button>

                    {{-- Insert from library --}}
                    <button type="button" wire:click="openLibrary({{ $sec->id }})" title="Insert from library"
                            class="mc-iconbtn"
                            onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    </button>

                    {{-- Delete --}}
                    <button type="button" wire:click="deleteSection({{ $sec->id }})" wire:confirm="Delete this section?"
                            class="mc-iconbtn"
                            onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                    </div>
                    @endif
                </div>

                @if($guidance)
                    <div class="mc-wa-guidance"><strong style="color:#6366f1;">Writing guidance:</strong> {{ $guidance }}</div>
                @endif

                <textarea rows="6" wire:key="content-{{ $sec->id }}"
                          wire:model.live.debounce.800ms="content.{{ $sec->id }}"
                          placeholder="Write your answer here…" @readonly(!$canManage)></textarea>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;font-size:11px;">
                    <span class="text-gray-400">{{ $words }} words</span>
                    <span style="color:{{ $over ? '#dc2626' : '#9ca3af' }};font-weight:{{ $over ? '600' : '400' }};">
                        {{ $count }}@if($limit) / {{ $limit }}@endif characters
                    </span>
                </div>

                <div class="mc-wa-review">
                    <select class="mc-wa-filter" wire:model.live="reviewStatuses.{{ $sec->id }}" @disabled(!$canManage)>
                        <option value="draft">Draft</option>
                        <option value="review">Needs review</option>
                        <option value="ready">Ready</option>
                    </select>
                    <input class="mc-wa-note" wire:model.blur="internalNotes.{{ $sec->id }}" placeholder="Internal reviewer note (not included in export)…" @readonly(!$canManage)>
                </div>
            </div>
        @empty
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 text-gray-500 dark:text-gray-400" style="padding:2rem;text-align:center;font-size:.8rem;">
                No questions match this filter.
            </div>
        @endforelse

        @if($canManage)
            <button type="button" wire:click="addSection"
                    class="text-gray-500 dark:text-gray-400"
                    style="width:100%;padding:12px;border:2px dashed rgba(100,116,139,.3);border-radius:12px;background:transparent;cursor:pointer;font-size:13px;font-weight:500;">
                + Add section
            </button>
        @endif
    @endif

        </main>

        <aside class="mc-wa-sidebar">
            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.5rem;">
                    <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Draft progress</span>
                    <span class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;">{{ $summary['progress'] }}% drafted · {{ $summary['completed'] }} / {{ $summary['total'] }}</span>
                </div>
                <div class="mc-wa-progress"><div style="height:100%;width:{{ $summary['progress'] }}%;background:#6366f1;border-radius:9999px;"></div></div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem;margin-top:.75rem;">
                    <div><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Drafted</p><p class="text-gray-950 dark:text-white" style="font-size:.92rem;font-weight:750;">{{ $summary['progress'] }}%</p></div>
                    <div><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Ready</p><p class="text-gray-950 dark:text-white" style="font-size:.92rem;font-weight:750;">{{ $summary['ready'] }}</p></div>
                    <div><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Over</p><p style="font-size:.92rem;font-weight:750;color:{{ $summary['over_limit'] > 0 ? '#dc2626' : 'inherit' }};">{{ $summary['over_limit'] }}</p></div>
                </div>
                <p class="text-gray-400" style="font-size:.66rem;margin-top:.5rem;">{{ $summary['completed'] }} of {{ $summary['total'] }} sections · {{ number_format($summary['words']) }} words · {{ $summary['in_review'] }} in review</p>
            </div>

            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Consistency</span>
                        <x-help-tip id="application-consistency-checker" title="Consistency checker">
                            Rule-based pre-review for missing answers, limits, unresolved reviews and Erasmus+ themes.
                        </x-help-tip>
                    </div>
                    <span style="font-size:1.35rem;font-weight:850;">{{ $review['score'] }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.1rem;">{{ $review['status'] }}</p>
                <div style="display:flex;gap:.32rem;flex-wrap:wrap;margin-top:.55rem;">
                    <span class="mc-review-pill mc-review-critical">{{ $review['critical'] }} critical</span>
                    <span class="mc-review-pill mc-review-warning">{{ $review['warning'] }} warnings</span>
                    <span class="mc-review-pill mc-review-suggestion">{{ $review['suggestion'] }} tips</span>
                </div>
                <div class="mc-review-issues" style="margin-top:.65rem;">
                    @forelse(array_slice($review['issues'], 0, 3) as $issue)
                        <div class="mc-review-issue mc-review-issue-compact">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                <span class="mc-review-pill mc-review-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span>
                                @if($issue['section_id'])
                                    <a href="#application-section-{{ $issue['section_id'] }}" style="font-size:.65rem;color:#6366f1;white-space:nowrap;">Open</a>
                                @endif
                            </div>
                            <p class="text-gray-950 dark:text-white" style="font-size:.7rem;font-weight:700;line-height:1.35;">{{ $issue['area'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;line-height:1.4;">{{ $issue['title'] }}</p>
                        </div>
                    @empty
                        <p style="padding:.65rem;border-radius:.6rem;background:rgba(34,197,94,.08);color:#15803d;font-size:.7rem;line-height:1.45;">No consistency issues detected.</p>
                    @endforelse
                </div>
                <button type="button" wire:click="openReviewDetails" style="width:100%;margin-top:.65rem;padding:.45rem .65rem;border:1px solid rgba(99,102,241,.25);border-radius:.55rem;background:rgba(99,102,241,.06);color:#4f46e5;font-size:.7rem;font-weight:700;cursor:pointer;">
                    View all checks
                </button>
            </div>

            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Quality review</span>
                        <x-help-tip id="application-quality-review" title="Quality review">
                            Evaluator-style review using Erasmus+ criteria. It shows where the argument needs more substance.
                        </x-help-tip>
                    </div>
                    <span style="font-size:1.35rem;font-weight:850;">{{ $quality['score'] }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.1rem;">{{ $quality['status'] }}</p>
                <div style="display:grid;gap:.45rem;margin-top:.65rem;">
                    @foreach($quality['criteria'] as $criterion)
                        <div class="mc-quality-card mc-quality-card-compact">
                            <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:center;">
                                <p class="text-gray-950 dark:text-white" style="font-size:.68rem;font-weight:750;line-height:1.25;">{{ $criterion['label'] }}</p>
                                <span class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;font-weight:750;">{{ $criterion['score'] }}</span>
                            </div>
                            <div class="mc-quality-bar"><span style="width:{{ $criterion['score'] }}%;background:{{ $criterion['score'] >= 80 ? '#10b981' : ($criterion['score'] >= 60 ? '#f59e0b' : '#ef4444') }};"></span></div>
                            <p class="text-gray-400" style="font-size:.61rem;">{{ $criterion['passed'] }} / {{ $criterion['total'] }} signals · {{ $criterion['status'] }}</p>
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="openReviewDetails" style="width:100%;margin-top:.65rem;padding:.45rem .65rem;border:1px solid rgba(16,185,129,.25);border-radius:.55rem;background:rgba(16,185,129,.06);color:#047857;font-size:.7rem;font-weight:700;cursor:pointer;">
                    View full quality review
                </button>
            </div>

            @if($sections->isNotEmpty())
                <div class="mc-wa-sidecard mc-wa-outline">
                    <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.55rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Outline</span>
                        <x-help-tip id="application-outline" title="Application outline">Useful for long applications. Use it to jump directly to a question; the green dot means the section contains text.</x-help-tip>
                    </div>
                    <div class="mc-wa-outline-list">
                        @foreach($categories as $category => $categorySections)
                            <div style="margin-bottom:.5rem;">
                                <p class="text-gray-400" style="font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.2rem .5rem;">{{ $category }}</p>
                                @foreach($categorySections as $outlineSection)
                                    @php $outlineFilled = trim(strip_tags($this->content[$outlineSection->id] ?? (string) $outlineSection->content)) !== ''; @endphp
                                    <a href="#application-section-{{ $outlineSection->id }}">
                                        <span style="width:6px;height:6px;flex:none;margin-top:.28rem;border-radius:9999px;background:{{ $outlineFilled ? '#22c55e' : 'rgba(148,163,184,.45)' }};"></span>
                                        <span>{{ \Illuminate\Support\Str::limit($outlineSection->title, 48) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>

    @if($showReviewDetails)
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="closeReviewDetails">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:86vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:1rem;font-weight:750;">Application review details</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;margin-top:.25rem;">Full consistency checks and evaluator-style quality signals for the current saved draft.</p>
                    </div>
                    <button class="mc-iconbtn" wire:click="closeReviewDetails">✕</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin-bottom:1rem;">
                    <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Consistency</p><p style="font-size:1.2rem;font-weight:800;">{{ $review['score'] }}</p></div>
                    <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Critical</p><p style="font-size:1.2rem;font-weight:800;color:{{ $review['critical'] ? '#dc2626' : 'inherit' }};">{{ $review['critical'] }}</p></div>
                    <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Warnings</p><p style="font-size:1.2rem;font-weight:800;color:{{ $review['warning'] ? '#d97706' : 'inherit' }};">{{ $review['warning'] }}</p></div>
                    <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Quality</p><p style="font-size:1.2rem;font-weight:800;">{{ $quality['score'] }}</p></div>
                </div>

                <div class="mc-review-detail-grid">
                    <section>
                        <p class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:750;margin-bottom:.55rem;">Consistency issues</p>
                        <div class="mc-review-detail-list">
                            @forelse($review['issues'] as $issue)
                                <div class="mc-review-issue mc-review-issue-compact">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                        <span class="mc-review-pill mc-review-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span>
                                        @if($issue['section_id'])
                                            <a href="#application-section-{{ $issue['section_id'] }}" wire:click="closeReviewDetails" style="font-size:.65rem;color:#6366f1;white-space:nowrap;">Open section</a>
                                        @endif
                                    </div>
                                    <p class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:750;line-height:1.35;">{{ $issue['area'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;line-height:1.45;">{{ $issue['title'] }}</p>
                                    <p style="font-size:.68rem;line-height:1.45;color:#4f46e5;">{{ $issue['action'] }}</p>
                                </div>
                            @empty
                                <p style="padding:.8rem;border-radius:.65rem;background:rgba(34,197,94,.08);color:#15803d;font-size:.75rem;">No consistency issues detected.</p>
                            @endforelse
                        </div>
                    </section>

                    <section>
                        <p class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:750;margin-bottom:.55rem;">Quality criteria</p>
                        <div class="mc-review-detail-list">
                            @foreach($quality['criteria'] as $criterion)
                                <div class="mc-quality-card">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                                        <div>
                                            <p class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">{{ $criterion['label'] }}</p>
                                            <p class="text-gray-400" style="font-size:.64rem;">{{ $criterion['passed'] }} / {{ $criterion['total'] }} signals · weight {{ $criterion['weight'] }}%</p>
                                        </div>
                                        <span style="font-size:1.1rem;font-weight:800;">{{ $criterion['score'] }}</span>
                                    </div>
                                    <div class="mc-quality-bar"><span style="width:{{ $criterion['score'] }}%;background:{{ $criterion['score'] >= 80 ? '#10b981' : ($criterion['score'] >= 60 ? '#f59e0b' : '#ef4444') }};"></span></div>
                                    <div style="display:grid;gap:.35rem;margin-top:.6rem;">
                                        @foreach($criterion['checks'] as $check)
                                            <div class="mc-check-row">
                                                <span style="width:18px;height:18px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;flex:none;background:{{ $check['passed'] ? 'rgba(34,197,94,.12)' : 'rgba(245,158,11,.12)' }};color:{{ $check['passed'] ? '#15803d' : '#b45309' }};font-weight:800;">{{ $check['passed'] ? '✓' : '!' }}</span>
                                                <div>
                                                    <p class="text-gray-950 dark:text-white" style="font-weight:700;">{{ $check['label'] }}</p>
                                                    <p class="text-gray-500 dark:text-gray-400">{{ $check['passed'] ? 'Signal detected in the current draft.' : $check['recommendation'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        </div>
    @endif

    @if($showTemplateDetails)
        @php
            $templateInfo = $this->getSelectedTemplateInfo();
            $alignment = $this->getTemplateAlignment();
            $catalog = $this->getTemplateCatalog();
        @endphp
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="closeTemplateDetails">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:86vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
                    <div>
                        <p style="font-size:1rem;font-weight:700;">Application template manager</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.76rem;margin-top:.25rem;">Choose the correct Erasmus+ action, compare the current draft with the official structure, then sync safely.</p>
                    </div>
                    <button class="mc-iconbtn" wire:click="closeTemplateDetails">✕</button>
                </div>

                <div class="mc-template-manager" style="margin-top:1rem;">
                    <div>
                        <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Template catalog</p>
                        <div style="display:grid;gap:.55rem;">
                            @foreach($catalog as $templateCard)
                                @php $isProjectTemplate = \App\Support\ApplicationTemplates::normaliseKey($record->ka_action) === $templateCard['key']; @endphp
                                <button type="button" wire:click="selectTemplate('{{ $templateCard['key'] }}')" class="mc-template-card {{ $selectedTemplate === $templateCard['key'] ? 'mc-template-card-active' : '' }}">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;">
                                        <span style="font-size:.78rem;font-weight:700;">{{ $templateCard['action'] }}</span>
                                        <span class="text-gray-400" style="font-size:.66rem;">Call {{ $templateCard['call_year'] }}</span>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.69rem;margin-top:.25rem;line-height:1.4;">{{ $templateCard['description'] }}</p>
                                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.5rem;">
                                        <x-filament::badge size="sm" color="gray">{{ $templateCard['sections_count'] }} questions</x-filament::badge>
                                        <x-filament::badge size="sm" color="{{ $isProjectTemplate ? 'success' : 'gray' }}">{{ $isProjectTemplate ? 'Current project' : $templateCard['form_id'] }}</x-filament::badge>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div style="border:1px solid rgba(148,163,184,.22);border-radius:.85rem;padding:1rem;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                                <div>
                                    <p style="font-size:.92rem;font-weight:750;">{{ $templateInfo['label'] ?? 'Application template' }}</p>
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;margin-top:.25rem;line-height:1.5;">{{ $templateInfo['description'] ?? '' }}</p>
                                </div>
                                <x-filament::badge color="{{ $alignment['is_current_project_template'] ? 'success' : 'warning' }}">
                                    {{ $alignment['is_current_project_template'] ? 'Selected in project' : 'Preview only' }}
                                </x-filament::badge>
                            </div>

                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin:.9rem 0;">
                                <x-filament::badge color="primary">Call {{ $templateInfo['call_year'] ?? '—' }}</x-filament::badge>
                                <x-filament::badge color="gray">{{ count($templateInfo['sections'] ?? []) }} writing questions</x-filament::badge>
                                <x-filament::badge color="gray">{{ $templateInfo['form_id'] ?? '' }}</x-filament::badge>
                            </div>

                            <div class="mc-template-stat-grid">
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Coverage</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['coverage'] }}%</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Matched</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['matched'] }} / {{ $alignment['official_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Missing</p><p style="font-size:1.15rem;font-weight:750;color:{{ $alignment['missing_count'] ? '#d97706' : 'inherit' }};">{{ $alignment['missing_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Custom</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['custom_count'] }}</p></div>
                            </div>

                            <div style="padding:.75rem;border-radius:.55rem;background:rgba(34,197,94,.08);color:#15803d;font-size:.74rem;line-height:1.5;margin-bottom:1rem;">
                                Safe sync: matching questions are updated, missing questions are added, and existing answers or custom sections are never deleted. A restorable backup is created automatically.
                            </div>

                            @if($alignment['missing_count'] || $alignment['metadata_sync_count'])
                                <div style="padding:.75rem;border-radius:.55rem;background:rgba(245,158,11,.09);color:#b45309;font-size:.74rem;line-height:1.5;margin-bottom:1rem;">
                                    This draft is not fully aligned yet: {{ $alignment['missing_count'] }} official questions are missing and {{ $alignment['metadata_sync_count'] }} matched questions need category or character-limit updates.
                                </div>
                            @else
                                <div style="padding:.75rem;border-radius:.55rem;background:rgba(99,102,241,.08);color:#4f46e5;font-size:.74rem;line-height:1.5;margin-bottom:1rem;">
                                    The current draft structure is aligned with this template. You can still sync if the template catalog was updated later.
                                </div>
                            @endif

                            @if($alignment['missing_count'])
                                <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;">Missing official questions</p>
                                <div class="mc-template-question-list" style="margin-bottom:1rem;">
                                    <ol style="margin:0 0 0 1.1rem;font-size:.72rem;line-height:1.55;">
                                        @foreach($alignment['missing'] as $question)
                                            <li style="margin-bottom:.25rem;"><span class="text-gray-400">{{ $question['category'] }}</span> · {{ $question['title'] }}</li>
                                        @endforeach
                                    </ol>
                                </div>
                            @endif

                            <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;">Official question structure</p>
                            <div class="mc-template-question-list">
                                <ol style="margin:0 0 0 1.1rem;font-size:.72rem;line-height:1.55;">
                                    @foreach($templateInfo['sections'] ?? [] as $question)
                                        <li style="margin-bottom:.25rem;"><span class="text-gray-400">{{ $question['category'] }}</span> · {{ $question['title'] }}</li>
                                    @endforeach
                                </ol>
                            </div>

                            <div style="margin-top:1rem;padding:.85rem;border-radius:.65rem;border:1px dashed rgba(99,102,241,.35);font-size:.73rem;line-height:1.55;">
                                <strong>Future calls, e.g. 2027:</strong> add the new official form to the template catalog as a new call-year version, open this manager, choose the new template, then synchronise. The project keeps a backup before the update, so older answers remain recoverable.
                            </div>
                        </div>

                        <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center;margin-top:1rem;">
                            <a href="{{ $templateInfo['source_url'] ?? '#' }}" target="_blank" rel="noopener" style="font-size:.72rem;color:#6366f1;">Open official form ↗</a>
                            <x-filament::button wire:click="loadTemplate" icon="heroicon-o-arrow-path">Synchronise selected template</x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showVersions)
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="$set('showVersions', false)">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:82vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <div><p style="font-size:1rem;font-weight:700;">Application versions</p><p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;">Create named checkpoints and safely restore previous drafts.</p></div>
                    <button class="mc-iconbtn" wire:click="$set('showVersions', false)">✕</button>
                </div>
                @if($canManage)
                    <div style="display:flex;gap:.55rem;margin-bottom:1rem;">
                        <input class="mc-wa-filter" style="flex:1;" wire:model="versionLabel" placeholder="Version label, e.g. Before partner review">
                        <x-filament::button wire:click="saveVersion" size="sm">Save current version</x-filament::button>
                    </div>
                @endif
                @forelse($this->getVersions() as $version)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.75rem 0;border-top:1px solid rgba(148,163,184,.18);">
                        <div><p style="font-size:.78rem;font-weight:650;">{{ $version->label }}</p><p class="text-gray-400" style="font-size:.68rem;">{{ $version->created_at->format('d M Y, H:i') }} · {{ count($version->snapshot) }} sections @if($version->creator) · {{ $version->creator->name }} @endif</p></div>
                        @if($canManage)<x-filament::button wire:click="restoreVersion({{ $version->id }})" wire:confirm="Restore this version? The current draft will be backed up first." color="gray" size="sm">Restore</x-filament::button>@endif
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;text-align:center;padding:1.5rem;">No saved versions yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    </div>

    {{-- ── Library picker modal ── --}}
    @if($showLibrary)
        <div class="mc-modal-backdrop mc-modal-top"
             wire:click.self="closeLibrary">
<div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="display:flex;flex-direction:column;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.2);">
                    <div style="font-size:15px;font-weight:600;">Insert from library</div>
                    <button type="button" wire:click="closeLibrary" class="mc-iconbtn" style="color:#9ca3af;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div style="padding:.9rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.15);">
                    <input type="text" wire:model.live.debounce.300ms="librarySearch"
                           placeholder="Search blocks…"
                           class="text-gray-950 dark:text-white"
                           style="width:100%;padding:9px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;">
                    <div class="text-gray-400" style="font-size:11px;margin-top:.4rem;">
                        Showing blocks for {{ strtoupper($record->ka_action ?: 'any action') }} and shared blocks.
                    </div>
                </div>

                <div style="overflow:auto;padding:1rem 1.25rem;">
                    @php $blocks = $this->getLibraryBlocks(); @endphp
                    @forelse($blocks as $b)
                        <div class="mc-libcard" wire:key="lib-{{ $b->id }}">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                                <div style="min-width:0;">
                                    <div style="font-weight:600;font-size:13px;margin-bottom:.25rem;">{{ $b->title }}</div>
                                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.4rem;">
                                        <x-filament::badge size="sm" color="primary">{{ $b->categoryLabel() }}</x-filament::badge>
                                        <x-filament::badge size="sm" color="gray">{{ strtoupper($b->ka_action) }}</x-filament::badge>
                                        @if($b->is_proven)<x-filament::badge size="sm" color="success">Proven</x-filament::badge>@endif
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400" style="font-size:12px;line-height:1.5;">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($b->body), 180) }}
                                    </div>
                                </div>
                                <button type="button" wire:click="insertBlock({{ $b->id }})"
                                        style="flex-shrink:0;padding:7px 14px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;font-weight:600;">
                                    Insert
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-500 dark:text-gray-400" style="text-align:center;padding:2rem 0;font-size:13px;">
                            No blocks found. Add some in <strong>Planning tools → Content Library</strong>.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <style>
        .mc-lib-modal { background:#ffffff !important; color:#18181b !important; }
        .dark .mc-lib-modal { background:#18212f !important; color:#f4f4f5 !important; }
    </style>
</x-filament-panels::page>
