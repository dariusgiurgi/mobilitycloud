<x-filament-panels::page>
    <x-ui-polish />
    @php
        $sections = $this->getSections();
        $visibleSections = $this->getVisibleSections();
        $summary = $this->getApplicationSummary();
        $review = $this->getConsistencyReview();
        $officialReview = $this->getOfficialCompletenessReview();
        $submissionChecklist = $this->getSubmissionChecklist();
        $quality = $this->getQualityReview();
        $reviewActionSummary = $this->getReviewActionSummary();
        $canManage = $record->canEditApplicationBy(auth()->user());
        $categories = $sections->groupBy(fn ($section) => $section->category ?: 'Custom sections');
        $isActivityTableTemplate = $this->isActivityTableTemplate();
        $activityTableChecklist = $this->getActivityTableChecklist();
        $sectionsWithTables = $this->getSectionsWithTables();
        $activityFlowSummary = $this->getActivityFlowSummary();
        $activityFlowReview = $this->getActivityFlowReview();
    @endphp

    <style>
        .mc-wa textarea { width:100%; background:transparent; border:1px solid rgba(100,116,139,.25); border-radius:8px; padding:10px 12px; font-size:13px; line-height:1.6; resize:vertical; color:inherit; }
        .mc-wa .mc-title { width:100%; background:transparent; border:none; font-size:15px; font-weight:650; color:inherit; padding:2px 0; line-height:1.45; resize:vertical; overflow:hidden; min-height:2.2rem; }
        .mc-wa select option { background:#fff; }
        .dark .mc-wa select option { background:#27303f; }
        .mc-iconbtn { flex-shrink:0; width:28px; height:28px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:#9ca3af; display:inline-flex; align-items:center; justify-content:center; }
        .mc-iconbtn:disabled { opacity:.25; cursor:default; }
        .mc-libcard { border:1px solid rgba(100,116,139,.2); border-radius:10px; padding:.85rem 1rem; margin-bottom:.6rem; }
        .mc-libcard:hover { border-color:#6366f1; }
        .mc-wa-toolbar { display:flex;align-items:center;gap:.65rem;flex-wrap:wrap; }
        .mc-wa-mode-switch { display:inline-flex;gap:.2rem;padding:.22rem;border:1px solid rgba(148,163,184,.24);border-radius:.7rem;background:rgba(148,163,184,.06); }
        .mc-wa-mode-btn { border:none;border-radius:.52rem;background:transparent;color:#64748b;font-size:.7rem;font-weight:750;padding:.38rem .62rem;cursor:pointer; }
        .mc-wa-mode-btn-active { background:#6366f1;color:white;box-shadow:0 6px 18px rgba(99,102,241,.22); }
        .mc-wa-layout { display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:.85rem;align-items:stretch;margin-top:1rem;height:clamp(560px,calc(100vh - 12.5rem),980px);overflow:hidden; }
        .mc-wa-layout-focus { grid-template-columns:minmax(0,920px);justify-content:center; }
        .mc-wa-main-scroll { min-width:0;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:0 .25rem .25rem 0;scroll-behavior:smooth; }
        .mc-wa-main-scroll::-webkit-scrollbar,.mc-wa-sidebar::-webkit-scrollbar { width:5px; }
        .mc-wa-main-scroll::-webkit-scrollbar-thumb,.mc-wa-sidebar::-webkit-scrollbar-thumb { border-radius:999px;background:rgba(148,163,184,.38); }
        .mc-wa-main-scroll::-webkit-scrollbar-track,.mc-wa-sidebar::-webkit-scrollbar-track { background:transparent; }
        .mc-wa-editor-controls { position:sticky;top:0;z-index:8;margin-bottom:1rem;padding:.55rem;border:1px solid rgba(148,163,184,.18);border-radius:.85rem;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);box-shadow:0 12px 26px rgba(15,23,42,.05); }
        .dark .mc-wa-editor-controls { background:rgba(17,24,39,.92);border-color:rgba(255,255,255,.1); }
        .mc-wa-sidebar { display:grid;align-content:start;gap:.48rem;min-height:0;max-height:100%;overflow-y:auto;overscroll-behavior:contain;padding-right:.12rem; }
        .mc-wa-sidecard { padding:.68rem .72rem;border:1px solid rgba(148,163,184,.22);border-radius:.78rem;background:#fff; }
        .mc-wa-sidebar .mc-wa-sidecard { padding:.56rem .62rem;border-radius:.68rem; }
        .mc-wa-sidebar p { line-height:1.35; }
        .mc-wa-sidebar .mc-wa-progress { height:6px; }
        .mc-wa-outline { padding:0; }
        .mc-wa-outline a { display:flex;align-items:flex-start;gap:.38rem;padding:.35rem .45rem;border-radius:.42rem;color:#64748b;text-decoration:none;font-size:.66rem;line-height:1.28;white-space:normal;overflow-wrap:anywhere; }
        .mc-wa-outline a:hover { color:#4f46e5;background:rgba(99,102,241,.07); }
        .mc-wa-outline-list { display:grid;gap:.2rem;max-height:none;overflow:visible; }
        .mc-wa-outline-details summary { list-style:none;cursor:pointer;padding:.68rem .72rem;display:flex;align-items:center;justify-content:space-between;gap:.6rem; }
        .mc-wa-outline-details summary::-webkit-details-marker { display:none; }
        .mc-wa-outline-details summary:after { content:'⌄';color:#94a3b8;font-size:.78rem;transition:transform .15s ease; }
        .mc-wa-outline-details[open] summary:after { transform:rotate(180deg); }
        .mc-wa-outline-details .mc-wa-outline-list { padding:0 .45rem .55rem; }
        .mc-wa-section { scroll-margin-top:1rem; }
        .mc-wa-card-actions { display:flex;align-items:center;gap:.1rem; }
        .mc-wa-progress { height:7px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden; }
        .mc-wa-guidance { margin:.15rem 0 .75rem;padding:.65rem .75rem;border-left:3px solid #818cf8;border-radius:.35rem;background:rgba(99,102,241,.07);font-size:.72rem;line-height:1.55;color:#64748b; }
        .mc-wa-hints { margin:.15rem 0 .8rem;border:1px solid rgba(148,163,184,.18);border-radius:.65rem;background:rgba(148,163,184,.035);font-size:.7rem;color:#64748b; }
        .mc-wa-hints summary { cursor:pointer;list-style:none;padding:.55rem .7rem;font-weight:750;color:#475569;display:flex;justify-content:space-between;gap:.75rem; }
        .mc-wa-hints summary::-webkit-details-marker { display:none; }
        .mc-wa-hints summary:after { content:'+';color:#94a3b8;font-weight:800; }
        .mc-wa-hints[open] summary:after { content:'–'; }
        .mc-wa-hints-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem;padding:0 .7rem .7rem; }
        .mc-wa-hints-grid p { font-weight:800;font-size:.58rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;color:#94a3b8; }
        .mc-wa-hints-grid ul { margin:0 0 0 .85rem;padding:0;line-height:1.45; }
        .mc-wa-table-block { margin:.15rem 0 .9rem;border:1px solid rgba(14,165,233,.18);border-radius:.7rem;background:rgba(14,165,233,.035);overflow:hidden; }
        .mc-wa-table-head { display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:.7rem .8rem;border-bottom:1px solid rgba(14,165,233,.12); }
        .mc-wa-table-wrap { overflow:auto; }
        .mc-wa-table { width:100%;border-collapse:collapse;font-size:.68rem;min-width:760px; }
        .mc-wa-table th { text-align:left;font-size:.56rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;background:rgba(148,163,184,.08);padding:.45rem .5rem;border-bottom:1px solid rgba(148,163,184,.18); }
        .mc-wa-table td { vertical-align:top;padding:.35rem .45rem;border-bottom:1px solid rgba(148,163,184,.12); }
        .mc-wa-table input { width:100%;border:1px solid rgba(148,163,184,.22);border-radius:.45rem;background:rgba(255,255,255,.65);color:inherit;font-size:.68rem;padding:.38rem .45rem; }
        .dark .mc-wa-table input { background:rgba(15,23,42,.7); }
        .mc-wa-table-empty { padding:.65rem .8rem;font-size:.68rem;color:#64748b; }
        .mc-wa-flow-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem; }
        .mc-wa-flow-row { border:1px solid rgba(148,163,184,.2);border-radius:.75rem;padding:.75rem;background:rgba(148,163,184,.035); }
        .mc-wa-flow-row input,.mc-wa-flow-row select { width:100%;border:1px solid rgba(148,163,184,.25);border-radius:.48rem;background:transparent;color:inherit;font-size:.68rem;padding:.42rem .48rem; }
        .mc-wa-flow-row label { display:grid;gap:.2rem;font-size:.58rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8; }
        .mc-wa-activity-mode { margin-bottom:1rem;padding:.85rem .95rem;border:1px solid rgba(14,165,233,.2);border-radius:.85rem;background:linear-gradient(135deg,rgba(14,165,233,.08),rgba(99,102,241,.055)); }
        .mc-wa-activity-mode ul { margin:.55rem 0 0 1rem;padding:0;font-size:.7rem;line-height:1.55;color:#64748b;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.25rem .9rem; }
        .mc-wa-readable-answer { min-height:7rem;border:1px solid rgba(148,163,184,.2);border-radius:.75rem;padding:1rem;background:rgba(148,163,184,.035);font-size:.86rem;line-height:1.75;white-space:pre-wrap; }
        .mc-wa-focus-topbar { display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;padding:.75rem .9rem;border:1px solid rgba(99,102,241,.2);border-radius:.85rem;background:rgba(99,102,241,.055); }
        .mc-wa-filter { width:auto;padding:7px 10px;border:1px solid rgba(100,116,139,.25);border-radius:7px;background:transparent;font-size:12px;color:inherit; }
        .mc-wa-review { display:grid;grid-template-columns:150px minmax(0,1fr);gap:.65rem;margin-top:.65rem;padding-top:.65rem;border-top:1px solid rgba(148,163,184,.18); }
        .mc-wa-review-actions { grid-column:1 / -1;display:flex;gap:.35rem;flex-wrap:wrap;align-items:center; }
        .mc-wa-review-chip { border:1px solid rgba(148,163,184,.24);border-radius:999px;background:transparent;color:#64748b;font-size:.65rem;font-weight:750;padding:.24rem .55rem;cursor:pointer; }
        .mc-wa-review-chip:hover { border-color:#818cf8;color:#4f46e5;background:rgba(99,102,241,.06); }
        .mc-wa-review-chip-active { border-color:transparent;background:#6366f1;color:white; }
        .mc-wa-queue-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.3rem;margin-top:.5rem; }
        .mc-wa-queue-btn { text-align:left;border:1px solid rgba(148,163,184,.2);border-radius:.55rem;background:rgba(148,163,184,.04);padding:.42rem;cursor:pointer;color:inherit; }
        .mc-wa-queue-btn:hover { border-color:#818cf8;background:rgba(99,102,241,.06); }
        .mc-wa-queue-active { border-color:#6366f1;background:rgba(99,102,241,.1); }
        .mc-wa-note { width:100%;padding:7px 9px;border:1px solid rgba(100,116,139,.22);border-radius:7px;background:transparent;font-size:11px;color:inherit; }
        .mc-template-manager { display:grid;grid-template-columns:300px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-template-search { width:100%;border:1px solid rgba(148,163,184,.25);border-radius:.65rem;background:transparent;color:inherit;font-size:.74rem;padding:.58rem .7rem;margin:.65rem 0; }
        .mc-template-card { width:100%;text-align:left;border:1px solid rgba(148,163,184,.22);border-radius:.75rem;background:transparent;padding:.75rem;cursor:pointer;color:inherit; }
        .mc-template-card:hover { border-color:#818cf8;background:rgba(99,102,241,.05); }
        .mc-template-card-active { border-color:#6366f1;background:rgba(99,102,241,.08); }
        .mc-template-family-tabs { display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.65rem; }
        .mc-template-family-tab { border:1px solid rgba(148,163,184,.22);border-radius:999px;padding:.28rem .62rem;font-size:.68rem;font-weight:700;color:inherit;background:transparent; }
        .mc-template-family-tab-active { border-color:#6366f1;background:rgba(99,102,241,.10);color:#4f46e5; }
        .mc-template-stat-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin:.8rem 0; }
        .mc-template-stat { border:1px solid rgba(148,163,184,.18);border-radius:.65rem;padding:.7rem;background:rgba(148,163,184,.05); }
        .mc-template-switch-preview { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:1rem; }
        .mc-template-switch-preview .mc-template-stat { padding:.6rem; }
        .mc-template-question-list { max-height:250px;overflow:auto;border:1px solid rgba(148,163,184,.18);border-radius:.65rem;padding:.7rem; }
        .mc-template-audit-grid { display:grid;grid-template-columns:180px minmax(0,1fr);gap:.85rem;align-items:start;margin-bottom:1rem; }
        .mc-template-audit-score { border:1px solid rgba(99,102,241,.18);border-radius:.75rem;padding:.85rem;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(14,165,233,.06)); }
        .mc-template-audit-list { display:grid;gap:.45rem;max-height:230px;overflow:auto;padding-right:.2rem; }
        .mc-template-audit-item { border:1px solid rgba(148,163,184,.18);border-radius:.58rem;padding:.55rem .65rem;background:rgba(148,163,184,.04);font-size:.68rem;line-height:1.4; }
        .mc-version-diff { border:1px solid rgba(99,102,241,.18);border-radius:.85rem;background:rgba(99,102,241,.045);padding:.9rem;margin-bottom:1rem; }
        .mc-version-diff-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin:.75rem 0; }
        .mc-version-change-list { display:grid;gap:.48rem;max-height:330px;overflow:auto;padding-right:.2rem; }
        .mc-version-change { border:1px solid rgba(148,163,184,.2);border-radius:.65rem;background:rgba(255,255,255,.55);padding:.65rem .75rem; }
        .dark .mc-version-change { background:rgba(15,23,42,.45); }
        .mc-review-panel { display:grid;grid-template-columns:180px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-review-score { border-radius:.9rem;padding:1rem;background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(14,165,233,.08));border:1px solid rgba(99,102,241,.18); }
        .mc-review-issues { display:grid;gap:.55rem; }
        .mc-review-issue { display:grid;grid-template-columns:88px minmax(0,1fr) auto;gap:.7rem;align-items:start;padding:.72rem .82rem;border:1px solid rgba(148,163,184,.18);border-radius:.7rem;background:rgba(148,163,184,.04); }
        .mc-review-issue-compact { grid-template-columns:1fr;gap:.35rem;padding:.58rem .62rem; }
        .mc-review-pill { display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:.18rem .5rem;font-size:.61rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em; }
        .mc-review-critical { color:#b91c1c;background:rgba(239,68,68,.11); }
        .mc-review-warning { color:#b45309;background:rgba(245,158,11,.12); }
        .mc-review-suggestion { color:#4f46e5;background:rgba(99,102,241,.1); }
        .mc-quality-grid { display:grid;grid-template-columns:220px minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-quality-main { border-radius:.95rem;padding:1rem;background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(99,102,241,.08));border:1px solid rgba(16,185,129,.18); }
        .mc-quality-criteria { display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.55rem; }
        .mc-quality-card { border:1px solid rgba(148,163,184,.18);border-radius:.75rem;padding:.75rem;background:rgba(148,163,184,.04);min-width:0; }
        .mc-quality-card-compact { padding:.52rem; }
        .mc-quality-bar { height:6px;border-radius:999px;background:rgba(148,163,184,.2);overflow:hidden;margin:.45rem 0 .5rem; }
        .mc-quality-bar > span { display:block;height:100%;border-radius:999px;background:#10b981; }
        .mc-quality-card ul { margin:.45rem 0 0 1rem;padding:0;font-size:.66rem;line-height:1.45;color:#64748b; }
        .mc-review-detail-grid { display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1rem;align-items:start; }
        .mc-review-detail-list { display:grid;gap:.55rem;max-height:58vh;overflow:auto;padding-right:.25rem; }
        .mc-check-row { display:flex;gap:.55rem;align-items:flex-start;padding:.55rem .65rem;border:1px solid rgba(148,163,184,.16);border-radius:.6rem;background:rgba(148,163,184,.04);font-size:.7rem;line-height:1.45; }
        .dark .mc-wa-sidecard { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        @media (max-width:1200px) { .mc-quality-grid { grid-template-columns:1fr; }.mc-quality-criteria { grid-template-columns:repeat(2,minmax(0,1fr)); }.mc-wa-hints-grid { grid-template-columns:1fr; }.mc-wa-flow-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:1100px) { .mc-wa-layout { grid-template-columns:1fr;height:auto;overflow:visible; }.mc-wa-main-scroll { overflow:visible;padding-right:0; }.mc-wa-editor-controls { position:static; }.mc-wa-sidebar { grid-row:1;max-height:none;overflow:visible;padding-right:0; }.mc-wa-outline-list { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:none; }.mc-template-manager { grid-template-columns:1fr; }.mc-template-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }.mc-template-audit-grid { grid-template-columns:1fr; }.mc-review-panel { grid-template-columns:1fr; }.mc-review-detail-grid { grid-template-columns:1fr; }.mc-review-detail-list { max-height:none; } }
        @media (max-width:650px) { .mc-wa-outline-list { grid-template-columns:1fr; }.mc-template-stat-grid { grid-template-columns:1fr; }.mc-version-diff-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }.mc-review-issue { grid-template-columns:1fr; }.mc-quality-criteria { grid-template-columns:1fr; } }
    </style>

    @if(! $canManage && $record->canBeManagedBy(auth()->user()))
        <div style="margin-bottom:1rem;padding:.9rem 1rem;border:1px solid rgba(59,130,246,.22);border-radius:.9rem;background:rgba(59,130,246,.08);color:#1d4ed8;font-size:.82rem;line-height:1.55;">
            <strong>Application locked.</strong>
            This project is no longer in the writing/revision stage, so the application is read-only. Use the management modules after approval for budget, participants, documents and mobility evidence.
        </div>
    @endif

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
                <div class="mc-wa-mode-switch" aria-label="Writing mode">
                    <button type="button" wire:click="setWritingMode('edit')" class="mc-wa-mode-btn {{ $writingMode === 'edit' ? 'mc-wa-mode-btn-active' : '' }}">Write</button>
                    <button type="button" wire:click="setWritingMode('review')" class="mc-wa-mode-btn {{ $writingMode === 'review' ? 'mc-wa-mode-btn-active' : '' }}">Review</button>
                    <button type="button" wire:click="setWritingMode('focus')" class="mc-wa-mode-btn {{ $writingMode === 'focus' ? 'mc-wa-mode-btn-active' : '' }}">Focus</button>
                </div>
                <x-filament::button wire:click="openTemplateDetails" size="sm" icon="heroicon-o-squares-2x2">
                    Template manager
                </x-filament::button>
            @endif
            <x-filament::button tag="a" :href="route('projects.export-application', $record)" target="_blank" icon="heroicon-o-arrow-down-tray" size="sm">
                Export PDF
            </x-filament::button>
            <x-filament::dropdown placement="bottom-end" width="xs">
                <x-slot name="trigger">
                    <x-filament::button color="gray" icon="heroicon-o-ellipsis-horizontal" size="sm">
                        More
                    </x-filament::button>
                </x-slot>

                <x-filament::dropdown.list>
                    @if($canManage)
                        <x-filament::dropdown.list.item wire:click="$set('showVersions', true)" icon="heroicon-o-clock">
                            Versions
                        </x-filament::dropdown.list.item>
                        @if($this->supportsActivityBuilder())
                            <x-filament::dropdown.list.item wire:click="openActivityBuilder" icon="heroicon-o-table-cells">
                                Activities & flows
                            </x-filament::dropdown.list.item>
                        @endif
                    @endif
                    <x-filament::dropdown.list.item tag="a" :href="route('projects.export-application-word', $record)" target="_blank" icon="heroicon-o-document-text">
                        Export Word
                    </x-filament::dropdown.list.item>
                    <x-filament::dropdown.list.item tag="a" :href="route('projects.export-application-pack', $record)" target="_blank" icon="heroicon-o-archive-box">
                        Export full pack
                    </x-filament::dropdown.list.item>
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        </div>
    </x-filament::section>

    <div class="mc-wa-layout {{ $writingMode === 'focus' ? 'mc-wa-layout-focus' : '' }}">
        <main class="mc-wa-main-scroll">

    @if($writingMode === 'focus')
        <div class="mc-wa-focus-topbar">
            <div>
                <p class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:750;">Focus mode</p>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.12rem;">One question at a time. Sidebar and review noise are hidden while you write.</p>
            </div>
            <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                <x-filament::button wire:click="moveFocus(-1)" color="gray" size="sm">Previous</x-filament::button>
                <x-filament::button wire:click="moveFocus(1)" color="gray" size="sm">Next</x-filament::button>
                <x-filament::button wire:click="setWritingMode('edit')" size="sm">Exit focus</x-filament::button>
            </div>
        </div>
    @elseif($sections->isNotEmpty())
        @if($isActivityTableTemplate)
            <div class="mc-wa-activity-mode">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                    <div>
                        <p class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:800;">Activity/table-driven application</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;margin-top:.15rem;line-height:1.45;">This template has fewer narrative fields because the official form relies heavily on activity and budget tables. Use the written answers to justify those tables, not to duplicate them.</p>
                    </div>
                    <x-filament::badge color="info">KA121 / KA151 mode</x-filament::badge>
                </div>
                <ul>
                    @foreach($activityTableChecklist as $activityHint)
                        <li>{{ $activityHint }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="mc-wa-editor-controls" style="display:flex;gap:.55rem;align-items:center;flex-wrap:wrap;">
            <input class="mc-wa-filter" style="min-width:230px;flex:1;" wire:model.live.debounce.300ms="sectionSearch" placeholder="Search questions or answers…">
            <select class="mc-wa-filter" wire:model.live="sectionFilter">
                <option value="all">All questions</option>
                <option value="draft">Draft</option>
                <option value="empty">Unanswered</option>
                <option value="official-issues">Official issues</option>
                <option value="tables">With standard tables</option>
                <option value="over-limit">Over character limit</option>
                <option value="review">Needs review</option>
                <option value="ready">Ready</option>
            </select>
            <span class="text-gray-400" style="font-size:.7rem;">{{ $visibleSections->count() }} shown</span>
        </div>
    @endif

    @if($sections->isEmpty())
        <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <x-filament::icon icon="heroicon-o-document-plus" class="mx-auto h-10 w-10 text-gray-400" />
            <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:.65rem 0 .25rem;">Start the application structure</h3>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;line-height:1.55;margin:0 auto {{ $canManage ? '1rem' : '0' }};max-width:34rem;">Choose an official KA template to load the correct application questions, or add a free section for a manual/internal project.</p>
            @if($canManage)
                <div style="display:flex;gap:.55rem;justify-content:center;flex-wrap:wrap;">
                    <x-filament::button wire:click="openTemplateDetails" icon="heroicon-o-squares-2x2">
                        Open Template manager
                    </x-filament::button>
                    <x-filament::button wire:click="addSection" color="gray" icon="heroicon-o-plus">
                        Add free section
                    </x-filament::button>
                </div>
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

            @php
                $guidance = $this->getQuestionGuidance($sec);
                $hints = $this->getQuestionHints($sec);
                $questionTables = $this->getQuestionTables($sec);
            @endphp

            <div id="application-section-{{ $sec->id }}" wire:key="section-{{ $sec->id }}" class="mc-wa-section fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.1rem 1.25rem;margin-bottom:1rem;">
                <div style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.6rem;">
                    <div style="min-width:0;flex:1;">
                        <textarea rows="{{ mb_strlen($titles[$sec->id] ?? (string) $sec->title) > 170 ? 4 : (mb_strlen($titles[$sec->id] ?? (string) $sec->title) > 95 ? 3 : 2) }}" wire:key="title-{{ $sec->id }}" class="mc-title text-gray-950 dark:text-white"
                                  wire:model.blur="titles.{{ $sec->id }}" @readonly(!$canManage)></textarea>
                        @if(count($questionTables))
                            <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.32rem;">
                                @foreach($questionTables as $tableBadge)
                                    <x-filament::badge size="sm" color="info">{{ $tableBadge['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($canManage && $writingMode !== 'review')
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

                    {{-- Insert scaffold --}}
                    <button type="button" wire:click="insertAnswerScaffold({{ $sec->id }})" title="Insert answer scaffold"
                            class="mc-iconbtn"
                            onmouseover="this.style.background='rgba(16,185,129,.1)';this.style.color='#059669';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    </button>

                    {{-- Focus --}}
                    <button type="button" wire:click="enterFocusMode({{ $sec->id }})" title="Focus this question"
                            class="mc-iconbtn"
                            onmouseover="this.style.background='rgba(14,165,233,.1)';this.style.color='#0284c7';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"></path><path d="M16 3h3a2 2 0 0 1 2 2v3"></path><path d="M8 21H5a2 2 0 0 1-2-2v-3"></path><path d="M16 21h3a2 2 0 0 0 2-2v-3"></path></svg>
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

                @if(! empty($hints))
                    <details class="mc-wa-hints">
                        <summary>Evaluator hints for this question</summary>
                        <div class="mc-wa-hints-grid">
                            <div>
                                <p>Expected</p>
                                <ul>
                                    @foreach($hints['expects'] as $hint)
                                        <li>{{ $hint }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <p>Evidence</p>
                                <ul>
                                    @foreach($hints['evidence'] as $hint)
                                        <li>{{ $hint }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <p>Avoid</p>
                                <ul>
                                    @foreach($hints['avoid'] as $hint)
                                        <li>{{ $hint }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </details>
                @endif

                @foreach($questionTables as $tableDef)
                    @php
                        $tableRows = $tables[$sec->id][$tableDef['key']] ?? [];
                        $autofillSummary = $this->getTableAutofillSummary($tableDef['key']);
                    @endphp
                    <div class="mc-wa-table-block">
                        <div class="mc-wa-table-head">
                            <div>
                                <p class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:800;">{{ $tableDef['label'] }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;margin-top:.12rem;line-height:1.4;">{{ $tableDef['description'] }}</p>
                            </div>
                            @if($canManage && $writingMode !== 'review')
                                <div style="display:flex;gap:.35rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                                    @if($autofillSummary)
                                        <button type="button" wire:click="autofillTable({{ $sec->id }}, '{{ $tableDef['key'] }}')" wire:confirm="Refresh this table from current project data? Existing rows in this table will be replaced." class="mc-wa-review-chip" style="white-space:nowrap;">Populate from project</button>
                                    @endif
                                    <button type="button" wire:click="addTableRow({{ $sec->id }}, '{{ $tableDef['key'] }}')" class="mc-wa-review-chip" style="white-space:nowrap;">+ Add row</button>
                                </div>
                            @endif
                        </div>
                        @if($autofillSummary)
                            <div style="padding:.45rem .8rem;border-bottom:1px solid rgba(14,165,233,.1);font-size:.64rem;color:#64748b;background:rgba(14,165,233,.035);">
                                Auto-fill: {{ $autofillSummary }} You can edit the generated rows before export.
                            </div>
                        @endif
                        @if(count($tableRows))
                            <div class="mc-wa-table-wrap">
                                <table class="mc-wa-table">
                                    <thead>
                                        <tr>
                                            @foreach($tableDef['columns'] as $column)
                                                <th>{{ $column['label'] }}</th>
                                            @endforeach
                                            @if($canManage && $writingMode !== 'review')
                                                <th style="width:44px;"></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tableRows as $rowIndex => $row)
                                            <tr>
                                                @foreach($tableDef['columns'] as $column)
                                                    <td>
                                                        @if($writingMode === 'review')
                                                            <span>{{ $row[$column['field']] ?? '—' }}</span>
                                                        @else
                                                            <input wire:model.live.debounce.800ms="tables.{{ $sec->id }}.{{ $tableDef['key'] }}.{{ $rowIndex }}.{{ $column['field'] }}" placeholder="{{ $column['label'] }}" @readonly(!$canManage)>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                @if($canManage && $writingMode !== 'review')
                                                    <td>
                                                        <button type="button" wire:click="removeTableRow({{ $sec->id }}, '{{ $tableDef['key'] }}', {{ $rowIndex }})" class="mc-iconbtn" title="Remove row">×</button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="mc-wa-table-empty">No rows yet. Add rows if this official question requires structured details.</div>
                        @endif
                    </div>
                @endforeach

                @if($writingMode === 'review')
                    <div class="mc-wa-readable-answer text-gray-700 dark:text-gray-200">
                        {{ trim(strip_tags($text)) !== '' ? trim(strip_tags($text)) : 'No answer yet.' }}
                    </div>
                @else
                    <textarea rows="{{ $writingMode === 'focus' ? 14 : 6 }}" wire:key="content-{{ $sec->id }}"
                              wire:model.live.debounce.800ms="content.{{ $sec->id }}"
                              placeholder="Write your answer here…" @readonly(!$canManage)></textarea>
                @endif

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
                    @if($canManage)
                        <div class="mc-wa-review-actions">
                            <button type="button" wire:click="setReviewStatus({{ $sec->id }}, 'draft')" class="mc-wa-review-chip {{ ($reviewStatuses[$sec->id] ?? $sec->review_status) === 'draft' ? 'mc-wa-review-chip-active' : '' }}">Draft</button>
                            <button type="button" wire:click="setReviewStatus({{ $sec->id }}, 'review')" class="mc-wa-review-chip {{ ($reviewStatuses[$sec->id] ?? $sec->review_status) === 'review' ? 'mc-wa-review-chip-active' : '' }}">Needs review</button>
                            <button type="button" wire:click="setReviewStatus({{ $sec->id }}, 'ready')" class="mc-wa-review-chip {{ ($reviewStatuses[$sec->id] ?? $sec->review_status) === 'ready' ? 'mc-wa-review-chip-active' : '' }}">Ready</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 text-gray-500 dark:text-gray-400" style="padding:2rem;text-align:center;font-size:.8rem;">
                No questions match this filter.
            </div>
        @endforelse

        @if($canManage && $writingMode !== 'review' && $writingMode !== 'focus')
            <button type="button" wire:click="addSection"
                    class="text-gray-500 dark:text-gray-400"
                    style="width:100%;padding:12px;border:2px dashed rgba(100,116,139,.3);border-radius:12px;background:transparent;cursor:pointer;font-size:13px;font-weight:500;">
                + Add section
            </button>
        @endif
    @endif

        </main>

        @if($writingMode !== 'focus')
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
                @if($canManage)
                    <div style="display:flex;gap:.35rem;margin-top:.65rem;flex-wrap:wrap;">
                        <button type="button" wire:click="focusNextEmpty" class="mc-wa-review-chip">Next empty</button>
                        <button type="button" wire:click="focusNextOfficialIssue" class="mc-wa-review-chip">Next official issue</button>
                    </div>
                @endif
            </div>

            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Review queue</span>
                        <x-help-tip id="application-review-queue" title="Review queue">
                            Use this to separate questions that are still being written from answers that need feedback and answers ready for export.
                        </x-help-tip>
                    </div>
                    <span class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;">{{ $summary['noted'] }} with notes</span>
                </div>
                <div class="mc-wa-queue-grid">
                    <button type="button" wire:click="filterReviewStatus('draft')" class="mc-wa-queue-btn {{ $sectionFilter === 'draft' ? 'mc-wa-queue-active' : '' }}">
                        <p class="text-gray-400" style="font-size:.57rem;text-transform:uppercase;font-weight:750;">Draft</p>
                        <p class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:850;">{{ $summary['draft'] }}</p>
                    </button>
                    <button type="button" wire:click="filterReviewStatus('review')" class="mc-wa-queue-btn {{ $sectionFilter === 'review' ? 'mc-wa-queue-active' : '' }}">
                        <p class="text-gray-400" style="font-size:.57rem;text-transform:uppercase;font-weight:750;">Review</p>
                        <p style="font-size:1.05rem;font-weight:850;color:{{ $summary['in_review'] > 0 ? '#d97706' : 'inherit' }};">{{ $summary['in_review'] }}</p>
                    </button>
                    <button type="button" wire:click="filterReviewStatus('ready')" class="mc-wa-queue-btn {{ $sectionFilter === 'ready' ? 'mc-wa-queue-active' : '' }}">
                        <p class="text-gray-400" style="font-size:.57rem;text-transform:uppercase;font-weight:750;">Ready</p>
                        <p style="font-size:1.05rem;font-weight:850;color:{{ $summary['ready'] > 0 ? '#059669' : 'inherit' }};">{{ $summary['ready'] }}</p>
                    </button>
                </div>
                @if($canManage)
                    <div style="display:grid;gap:.38rem;margin-top:.6rem;">
                        <button type="button" wire:click="sendIssueSectionsToReview" wire:confirm="Mark all section-specific checker issues as Needs review and append reviewer notes?" class="mc-wa-review-chip" style="width:100%;border-radius:.55rem;">
                            Flag {{ $reviewActionSummary['issue_sections'] }} issue sections
                        </button>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.38rem;">
                            <button type="button" wire:click="generateReviewNotesFromChecks" wire:confirm="Generate reviewer notes from the current checks?" class="mc-wa-review-chip" style="border-radius:.55rem;">Generate notes</button>
                            <button type="button" wire:click="markAnsweredSectionsReady" wire:confirm="Mark all answered sections as Ready?" class="mc-wa-review-chip" style="border-radius:.55rem;">Mark {{ $reviewActionSummary['answered'] }} ready</button>
                        </div>
                    </div>
                    <p class="text-gray-400" style="font-size:.62rem;line-height:1.4;margin-top:.45rem;">{{ $reviewActionSummary['empty'] }} empty sections stay Draft. Review notes are internal and never included in exports.</p>
                @endif
                @if($sectionFilter !== 'all')
                    <button type="button" wire:click="filterReviewStatus('all')" style="margin-top:.55rem;width:100%;border:1px solid rgba(148,163,184,.22);border-radius:.55rem;background:transparent;color:#64748b;padding:.42rem .6rem;font-size:.68rem;font-weight:750;cursor:pointer;">
                        Show all questions
                    </button>
                @endif
            </div>

            @if(count($sectionsWithTables))
                <div class="mc-wa-sidecard">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                        <div>
                            <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Standard tables</span>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.64rem;margin-top:.12rem;">Jump to official questions that include structured tables.</p>
                        </div>
                        <button type="button" wire:click="filterReviewStatus('tables')" class="mc-wa-review-chip {{ $sectionFilter === 'tables' ? 'mc-wa-review-chip-active' : '' }}">Show</button>
                    </div>
                    <div style="display:grid;gap:.45rem;margin-top:.65rem;">
                        @foreach($sectionsWithTables as $tableItem)
                            <a href="#application-section-{{ $tableItem['section']->id }}" style="display:block;text-decoration:none;padding:.55rem .6rem;border:1px solid rgba(148,163,184,.18);border-radius:.6rem;background:rgba(148,163,184,.04);">
                                <p class="text-gray-950 dark:text-white" style="font-size:.68rem;font-weight:750;line-height:1.35;">{{ $tableItem['section']->title }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.62rem;margin-top:.22rem;">{{ collect($tableItem['tables'])->pluck('label')->implode(', ') }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mc-wa-sidecard">
                    <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Standard tables</span>
                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;line-height:1.45;margin-top:.25rem;">No table-ready official questions in the current draft. Switch/sync to a template such as KA151, KA152, KA210 or KA220 to load questions that use tables.</p>
                </div>
            @endif

            @if($this->supportsActivityBuilder())
                <div class="mc-wa-sidecard">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                        <div>
                            <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Activities & flows</span>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.64rem;margin-top:.12rem;">Structured mobility plan used by tables and exports.</p>
                        </div>
                        <button type="button" wire:click="openActivityBuilder" class="mc-wa-review-chip">Open</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.35rem;margin-top:.65rem;">
                        <div><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Flows</p><p class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:800;">{{ $activityFlowSummary['count'] }}</p></div>
                        <div><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">People</p><p class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:800;">{{ $activityFlowSummary['participants'] }}</p></div>
                        <div><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Fewer</p><p class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:800;">{{ $activityFlowSummary['fewer'] }}</p></div>
                        <div><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Green</p><p class="text-gray-950 dark:text-white" style="font-size:.88rem;font-weight:800;">{{ $activityFlowSummary['green'] }}</p></div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:.55rem;">
                        <span class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;">Flow quality {{ $activityFlowReview['score'] }} · {{ $activityFlowReview['status'] }}</span>
                        @if($activityFlowReview['critical'] || $activityFlowReview['warning'])
                            <span class="mc-review-pill {{ $activityFlowReview['critical'] ? 'mc-review-critical' : 'mc-review-warning' }}">{{ $activityFlowReview['critical'] + $activityFlowReview['warning'] }} issues</span>
                        @endif
                    </div>
                </div>
            @endif

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
                <button type="button" wire:click="openReviewDetails" style="width:100%;margin-top:.65rem;padding:.45rem .65rem;border:1px solid rgba(99,102,241,.25);border-radius:.55rem;background:rgba(99,102,241,.06);color:#4f46e5;font-size:.7rem;font-weight:700;cursor:pointer;">
                    View checks
                </button>
            </div>

            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Official readiness</span>
                        <x-help-tip id="application-official-readiness" title="Official readiness">
                            Checks form logic: conditional answers, required structured tables and activity-flow consistency.
                        </x-help-tip>
                    </div>
                    <span style="font-size:1.35rem;font-weight:850;">{{ $officialReview['score'] }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.1rem;">{{ $officialReview['status'] }}</p>
                <div style="display:flex;gap:.32rem;flex-wrap:wrap;margin-top:.55rem;">
                    <span class="mc-review-pill mc-review-critical">{{ $officialReview['critical'] }} critical</span>
                    <span class="mc-review-pill mc-review-warning">{{ $officialReview['warning'] }} warnings</span>
                </div>
                <button type="button" wire:click="filterReviewStatus('official-issues')" class="mc-wa-review-chip {{ $sectionFilter === 'official-issues' ? 'mc-wa-review-chip-active' : '' }}" style="width:100%;margin-top:.65rem;border-radius:.55rem;">
                    View official issues
                </button>
            </div>

            <div class="mc-wa-sidecard">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:750;">Submission checklist</span>
                        <x-help-tip id="application-submission-checklist" title="Submission checklist">
                            Practical pre-submission checklist adapted to the selected KA action. It combines template, answer, flow, budget and quality signals.
                        </x-help-tip>
                    </div>
                    <span style="font-size:1.35rem;font-weight:850;">{{ $submissionChecklist['score'] }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.1rem;">{{ $submissionChecklist['status'] }}</p>
                <div style="display:flex;gap:.32rem;flex-wrap:wrap;margin-top:.55rem;">
                    <span class="mc-review-pill mc-review-suggestion">{{ $submissionChecklist['complete'] }} done</span>
                    <span class="mc-review-pill mc-review-warning">{{ $submissionChecklist['warning'] }} review</span>
                    <span class="mc-review-pill mc-review-critical">{{ $submissionChecklist['missing'] }} missing</span>
                </div>
                <button type="button" wire:click="openReviewDetails" style="width:100%;margin-top:.65rem;padding:.45rem .65rem;border:1px solid rgba(99,102,241,.25);border-radius:.55rem;background:rgba(99,102,241,.06);color:#4f46e5;font-size:.7rem;font-weight:700;cursor:pointer;">
                    View checklist
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
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.35rem;margin-top:.6rem;">
                    @foreach($quality['criteria'] as $criterion)
                        <div style="border:1px solid rgba(148,163,184,.18);border-radius:.55rem;padding:.42rem .48rem;background:rgba(148,163,184,.04);">
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.58rem;font-weight:750;line-height:1.2;">{{ $criterion['label'] }}</p>
                            <p style="font-size:.82rem;font-weight:850;color:{{ $criterion['score'] >= 80 ? '#059669' : ($criterion['score'] >= 60 ? '#d97706' : '#dc2626') }};">{{ $criterion['score'] }}</p>
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="openReviewDetails" style="width:100%;margin-top:.65rem;padding:.45rem .65rem;border:1px solid rgba(16,185,129,.25);border-radius:.55rem;background:rgba(16,185,129,.06);color:#047857;font-size:.7rem;font-weight:700;cursor:pointer;">
                    View quality details
                </button>
            </div>

        </aside>
        @endif
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

                <section style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center;margin-bottom:.55rem;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:750;">Submission checklist</p>
                        <span class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;">{{ $submissionChecklist['complete'] }} done · {{ $submissionChecklist['warning'] }} review · {{ $submissionChecklist['missing'] }} missing</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;">
                        @foreach($submissionChecklist['items'] as $item)
                            <div class="mc-review-issue mc-review-issue-compact">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                    <span class="mc-review-pill {{ $item['status'] === 'complete' ? 'mc-review-suggestion' : ($item['status'] === 'warning' ? 'mc-review-warning' : 'mc-review-critical') }}">{{ $item['status'] }}</span>
                                    @if($item['sectionId'])
                                        <a href="#application-section-{{ $item['sectionId'] }}" wire:click="closeReviewDetails" style="font-size:.65rem;color:#6366f1;white-space:nowrap;">Open section</a>
                                    @endif
                                </div>
                                <p class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:750;line-height:1.35;">{{ $item['label'] }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;line-height:1.45;">{{ $item['description'] }}</p>
                                <p style="font-size:.66rem;line-height:1.4;color:#4f46e5;">{{ $item['action'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

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
            $switchPreview = $this->getTemplateSwitchPreview();
            $sourceNotice = $this->getTemplateSourceNotice();
            $templateAudit = $this->getSelectedTemplateAudit();
            $templateAuditSummary = $this->getTemplateAuditSummary();
            $catalog = $this->getTemplateCatalog();
        @endphp
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="closeTemplateDetails">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:86vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
                    <div>
                        <p style="font-size:1rem;font-weight:700;">Application template manager</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.76rem;margin-top:.25rem;">Choose the correct Erasmus+ action, compare the current draft with the official structure, then switch safely. Only templates verified against the official application form are shown here.</p>
                    </div>
                    <button class="mc-iconbtn" wire:click="closeTemplateDetails">✕</button>
                </div>

                <div class="mc-template-manager" style="margin-top:1rem;">
                    <div>
                        <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Template catalog</p>
                        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.38rem;margin-bottom:.65rem;">
                            <div class="mc-template-stat" style="padding:.52rem;"><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Templates</p><p style="font-size:.9rem;font-weight:800;">{{ $templateAuditSummary['templates'] }}</p></div>
                            <div class="mc-template-stat" style="padding:.52rem;"><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Clean</p><p style="font-size:.9rem;font-weight:800;color:#059669;">{{ $templateAuditSummary['excellent'] }}</p></div>
                            <div class="mc-template-stat" style="padding:.52rem;"><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Review</p><p style="font-size:.9rem;font-weight:800;color:{{ $templateAuditSummary['needs_review'] ? '#d97706' : 'inherit' }};">{{ $templateAuditSummary['needs_review'] }}</p></div>
                            <div class="mc-template-stat" style="padding:.52rem;"><p class="text-gray-400" style="font-size:.55rem;text-transform:uppercase;">Tables</p><p style="font-size:.9rem;font-weight:800;">{{ $templateAuditSummary['tables'] }}</p></div>
                        </div>
                        <div class="mc-template-family-tabs">
                            @foreach($this->getTemplateFamilies() as $familyKey => $familyLabel)
                                <button type="button" wire:click="$set('templateCatalogFamily', '{{ $familyKey }}')" class="mc-template-family-tab {{ $templateCatalogFamily === $familyKey ? 'mc-template-family-tab-active' : '' }}">
                                    {{ $familyLabel }}
                                </button>
                            @endforeach
                        </div>
                        <input type="search" wire:model.live.debounce.250ms="templateCatalogSearch" class="mc-template-search" placeholder="Search by KA code, sector, form or keyword…">
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
                                        <x-filament::badge size="sm" color="primary">{{ \App\Support\ApplicationTemplates::families()[$templateCard['family']] ?? ucfirst($templateCard['family']) }}</x-filament::badge>
                                        @if($templateCard['sector'])
                                            <x-filament::badge size="sm" color="gray">{{ strtoupper($templateCard['sector']) }}</x-filament::badge>
                                        @endif
                                        <x-filament::badge size="sm" color="gray">{{ $templateCard['sections_count'] }} questions</x-filament::badge>
                                        <x-filament::badge size="sm" color="{{ $templateCard['audit_score'] >= 90 ? 'success' : ($templateCard['audit_score'] >= 78 ? 'warning' : 'danger') }}">Audit {{ $templateCard['audit_score'] }}</x-filament::badge>
                                        @if($templateCard['audit_table_count'])
                                            <x-filament::badge size="sm" color="info">{{ $templateCard['audit_table_count'] }} tables</x-filament::badge>
                                        @endif
                                        <x-filament::badge size="sm" color="{{ ($templateCard['officially_verified'] ?? false) ? 'success' : 'warning' }}">{{ ($templateCard['officially_verified'] ?? false) ? 'Verified' : 'Draft' }}</x-filament::badge>
                                        <x-filament::badge size="sm" color="{{ $isProjectTemplate ? 'success' : 'gray' }}">{{ $isProjectTemplate ? 'Current project' : $templateCard['form_id'] }}</x-filament::badge>
                                    </div>
                                </button>
                            @endforeach
                            @if(count($catalog) === 0)
                                <div style="padding:.75rem;border-radius:.65rem;border:1px dashed rgba(148,163,184,.3);font-size:.73rem;color:#64748b;">No templates match this search.</div>
                            @endif
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
                                <x-filament::badge color="{{ ($templateInfo['officially_verified'] ?? false) ? 'success' : 'warning' }}">{{ ($templateInfo['officially_verified'] ?? false) ? 'Officially verified' : 'Not verified' }}</x-filament::badge>
                            </div>

                            @if($sourceNotice)
                                <div style="padding:.75rem;border-radius:.55rem;background:{{ $sourceNotice['tone'] === 'success' ? 'rgba(34,197,94,.08)' : 'rgba(245,158,11,.09)' }};color:{{ $sourceNotice['tone'] === 'success' ? '#15803d' : '#b45309' }};font-size:.74rem;line-height:1.5;margin-bottom:1rem;">
                                    <strong>{{ $sourceNotice['title'] }}:</strong> {{ $sourceNotice['body'] }}
                                </div>
                            @endif

                            <div class="mc-template-stat-grid">
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Coverage</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['coverage'] }}%</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Matched</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['matched'] }} / {{ $alignment['official_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Missing</p><p style="font-size:1.15rem;font-weight:750;color:{{ $alignment['missing_count'] ? '#d97706' : 'inherit' }};">{{ $alignment['missing_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.62rem;text-transform:uppercase;">Custom</p><p style="font-size:1.15rem;font-weight:750;">{{ $alignment['custom_count'] }}</p></div>
                            </div>

                            <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;">Template audit</p>
                            <div class="mc-template-audit-grid">
                                <div class="mc-template-audit-score">
                                    <p class="text-gray-400" style="font-size:.6rem;text-transform:uppercase;font-weight:750;">Audit score</p>
                                    <p style="font-size:2rem;font-weight:850;line-height:1;color:{{ $templateAudit['score'] >= 90 ? '#059669' : ($templateAudit['score'] >= 78 ? '#d97706' : '#dc2626') }};">{{ $templateAudit['score'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;margin-top:.35rem;">{{ $templateAudit['status'] }} · {{ $templateAudit['counts']['sections'] }} questions · {{ $templateAudit['counts']['categories'] }} categories</p>
                                    <p class="text-gray-400" style="font-size:.64rem;margin-top:.35rem;">{{ $templateAudit['counts']['with_guidance'] }} with guidance · {{ $templateAudit['counts']['with_char_limits'] }} with character limits</p>
                                </div>
                                <div>
                                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.55rem;">
                                        <x-filament::badge color="{{ count($templateAudit['issues']) ? 'warning' : 'success' }}">{{ count($templateAudit['issues']) }} audit issues</x-filament::badge>
                                        <x-filament::badge color="{{ count($templateAudit['tables']) ? 'info' : 'gray' }}">{{ count($templateAudit['tables']) }} detected standard tables</x-filament::badge>
                                    </div>
                                    <div class="mc-template-audit-list">
                                        @forelse($templateAudit['issues'] as $auditIssue)
                                            <div class="mc-template-audit-item">
                                                <div style="display:flex;align-items:center;gap:.45rem;margin-bottom:.2rem;">
                                                    <span class="mc-review-pill mc-review-{{ $auditIssue['severity'] }}">{{ $auditIssue['severity'] }}</span>
                                                    <strong>{{ $auditIssue['title'] }}</strong>
                                                </div>
                                                <span class="text-gray-500 dark:text-gray-400">{{ $auditIssue['description'] }}</span>
                                            </div>
                                        @empty
                                            <div class="mc-template-audit-item" style="color:#15803d;background:rgba(34,197,94,.08);">No structural audit issues detected.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            @if(count($templateAudit['tables']))
                                <div style="padding:.75rem;border-radius:.55rem;background:rgba(14,165,233,.07);color:#0369a1;font-size:.72rem;line-height:1.5;margin-bottom:1rem;">
                                    <strong>Detected tables:</strong>
                                    {{ collect($templateAudit['tables'])->pluck('label')->unique()->implode(', ') }}.
                                </div>
                            @endif

                            <div style="padding:.75rem;border-radius:.55rem;background:rgba(34,197,94,.08);color:#15803d;font-size:.74rem;line-height:1.5;margin-bottom:1rem;">
                                Safe switch: this replaces the official question structure with the selected template, keeps custom sections, preserves answers on matching questions, and creates a restorable backup automatically.
                            </div>

                            <p class="text-gray-400" style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;">Switch impact preview</p>
                            <div class="mc-template-switch-preview">
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Added</p><p style="font-size:1rem;font-weight:800;color:{{ $switchPreview['added_count'] ? '#4f46e5' : 'inherit' }};">{{ $switchPreview['added_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Matched</p><p style="font-size:1rem;font-weight:800;color:#059669;">{{ $switchPreview['matched_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Removed</p><p style="font-size:1rem;font-weight:800;color:{{ $switchPreview['removed_count'] ? '#dc2626' : 'inherit' }};">{{ $switchPreview['removed_count'] }}</p></div>
                                <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Custom kept</p><p style="font-size:1rem;font-weight:800;">{{ $switchPreview['custom_preserved_count'] }}</p></div>
                            </div>

                            @if($switchPreview['removed_count'])
                                <div style="padding:.75rem;border-radius:.55rem;background:rgba(239,68,68,.08);color:#b91c1c;font-size:.72rem;line-height:1.5;margin-bottom:1rem;">
                                    <strong>Old official questions that would be removed:</strong>
                                    <ul style="margin:.35rem 0 0 1rem;padding:0;">
                                        @foreach($switchPreview['removed'] as $removedTitle)
                                            <li>{{ $removedTitle }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif($switchPreview['added_count'])
                                <div style="padding:.75rem;border-radius:.55rem;background:rgba(99,102,241,.08);color:#4f46e5;font-size:.72rem;line-height:1.5;margin-bottom:1rem;">
                                    <strong>New official questions to add:</strong>
                                    <ul style="margin:.35rem 0 0 1rem;padding:0;">
                                        @foreach($switchPreview['added'] as $addedTitle)
                                            <li>{{ $addedTitle }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

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

                        </div>

                        <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center;margin-top:1rem;">
                            <a href="{{ $templateInfo['source_url'] ?? '#' }}" target="_blank" rel="noopener" style="font-size:.72rem;color:#6366f1;">Open official form ↗</a>
                            <x-filament::button wire:click="loadTemplate" icon="heroicon-o-arrow-path">Switch to selected template</x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showVersions)
        @php $versionDiff = $this->getVersionDiff(); @endphp
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="$set('showVersions', false)">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:82vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <div><p style="font-size:1rem;font-weight:700;">Application versions</p><p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;">Create named checkpoints, compare them with the current draft and safely restore previous states.</p></div>
                    <button class="mc-iconbtn" wire:click="$set('showVersions', false)">✕</button>
                </div>
                @if($canManage)
                    <div style="display:flex;gap:.55rem;margin-bottom:1rem;">
                        <input class="mc-wa-filter" style="flex:1;" wire:model="versionLabel" placeholder="Version label, e.g. Before partner review">
                        <x-filament::button wire:click="saveVersion" size="sm">Save current version</x-filament::button>
                    </div>
                @endif

                @if($versionDiff)
                    <div class="mc-version-diff">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                            <div>
                                <p class="text-gray-950 dark:text-white" style="font-size:.86rem;font-weight:800;">Comparing with: {{ $versionDiff['version']->label }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.18rem;">Saved {{ $versionDiff['version']->created_at->format('d M Y, H:i') }} @if($versionDiff['version']->creator) by {{ $versionDiff['version']->creator->name }} @endif · compared against the current draft</p>
                            </div>
                            <button class="mc-wa-review-chip" wire:click="closeVersionDiff">Close diff</button>
                        </div>
                        <div class="mc-version-diff-grid">
                            <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.56rem;text-transform:uppercase;">Added</p><p style="font-size:1.05rem;font-weight:850;color:#4f46e5;">{{ $versionDiff['summary']['added'] }}</p></div>
                            <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.56rem;text-transform:uppercase;">Removed</p><p style="font-size:1.05rem;font-weight:850;color:#dc2626;">{{ $versionDiff['summary']['removed'] }}</p></div>
                            <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.56rem;text-transform:uppercase;">Modified</p><p style="font-size:1.05rem;font-weight:850;color:#d97706;">{{ $versionDiff['summary']['modified'] }}</p></div>
                            <div class="mc-template-stat"><p class="text-gray-400" style="font-size:.56rem;text-transform:uppercase;">Unchanged</p><p style="font-size:1.05rem;font-weight:850;">{{ $versionDiff['summary']['unchanged'] }}</p></div>
                        </div>
                        <div class="mc-version-change-list">
                            @forelse($versionDiff['changes'] as $change)
                                <div class="mc-version-change">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;">
                                        <div>
                                            <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                                                <span class="mc-review-pill {{ $change['type'] === 'removed' ? 'mc-review-critical' : ($change['type'] === 'added' ? 'mc-review-suggestion' : 'mc-review-warning') }}">{{ $change['type'] }}</span>
                                                @if($change['category'])<span class="text-gray-400" style="font-size:.62rem;">{{ $change['category'] }}</span>@endif
                                            </div>
                                            <p class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:750;line-height:1.35;margin-top:.3rem;">{{ $change['title'] }}</p>
                                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;margin-top:.18rem;">Changed: {{ implode(', ', $change['fields']) }}</p>
                                        </div>
                                    </div>
                                    @if($change['before'] || $change['after'])
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.55rem;">
                                            <div style="font-size:.65rem;color:#64748b;"><strong>Version:</strong><br>{{ $change['before'] ?: '—' }}</div>
                                            <div style="font-size:.65rem;color:#64748b;"><strong>Current:</strong><br>{{ $change['after'] ?: '—' }}</div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="mc-version-change" style="color:#15803d;background:rgba(34,197,94,.08);">No differences detected between this version and the current draft.</div>
                            @endforelse
                        </div>
                    </div>
                @endif

                @forelse($this->getVersions() as $version)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.75rem 0;border-top:1px solid rgba(148,163,184,.18);">
                        <div><p style="font-size:.78rem;font-weight:650;">{{ $version->label }}</p><p class="text-gray-400" style="font-size:.68rem;">{{ $version->created_at->format('d M Y, H:i') }} · {{ count($version->snapshot) }} sections @if($version->creator) · {{ $version->creator->name }} @endif</p></div>
                        <div style="display:flex;gap:.35rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                            <x-filament::button wire:click="openVersionDiff({{ $version->id }})" color="gray" size="sm">View diff</x-filament::button>
                            @if($canManage)<x-filament::button wire:click="restoreVersion({{ $version->id }})" wire:confirm="Restore this version? The current draft will be backed up first." color="gray" size="sm">Restore</x-filament::button>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;text-align:center;padding:1.5rem;">No saved versions yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if($showActivityBuilder)
        <div class="mc-modal-backdrop mc-modal-top" wire:click.self="closeActivityBuilder">
            <div class="mc-lib-modal mc-modal-panel mc-modal-panel-wide" style="padding:1.35rem;max-height:86vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:1rem;font-weight:750;">Activities & mobility flows</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;margin-top:.25rem;">Use this as the structured backbone for KA mobility applications. These rows feed the activity plan table, official checks and the application pack export.</p>
                    </div>
                    <button class="mc-iconbtn" wire:click="closeActivityBuilder">✕</button>
                </div>

                <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:1rem;">
                    @if($canManage)
                        <x-filament::button wire:click="generateActivityFlowsFromParticipants" color="gray" size="sm">Generate from participants</x-filament::button>
                        <x-filament::button wire:click="syncActivityFlowDurations" color="gray" size="sm">Recalculate durations</x-filament::button>
                        <x-filament::button wire:click="addActivityFlow" size="sm">+ Add flow</x-filament::button>
                    @endif
                    <x-filament::badge color="info">{{ $activityFlowSummary['count'] }} flows</x-filament::badge>
                    <x-filament::badge color="gray">{{ $activityFlowSummary['participants'] }} participants</x-filament::badge>
                    <x-filament::badge color="gray">{{ $activityFlowSummary['fewer'] }} fewer opportunities</x-filament::badge>
                    <x-filament::badge color="{{ $activityFlowReview['score'] >= 90 ? 'success' : ($activityFlowReview['score'] >= 72 ? 'warning' : 'danger') }}">Quality {{ $activityFlowReview['score'] }}</x-filament::badge>
                </div>

                <div style="display:grid;grid-template-columns:180px minmax(0,1fr);gap:.75rem;margin-bottom:1rem;">
                    <div class="mc-template-stat">
                        <p class="text-gray-400" style="font-size:.58rem;text-transform:uppercase;">Flow review</p>
                        <p style="font-size:1.5rem;font-weight:850;color:{{ $activityFlowReview['score'] >= 90 ? '#059669' : ($activityFlowReview['score'] >= 72 ? '#d97706' : '#dc2626') }};">{{ $activityFlowReview['score'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;line-height:1.35;">{{ $activityFlowReview['status'] }}</p>
                    </div>
                    <div style="display:grid;gap:.42rem;max-height:170px;overflow:auto;padding-right:.2rem;">
                        @forelse($activityFlowReview['issues'] as $flowIssue)
                            <div class="mc-version-change" style="padding:.55rem .65rem;">
                                <div style="display:flex;gap:.45rem;align-items:center;margin-bottom:.18rem;">
                                    <span class="mc-review-pill mc-review-{{ $flowIssue['severity'] }}">{{ $flowIssue['severity'] }}</span>
                                    <strong style="font-size:.7rem;">{{ $flowIssue['title'] }}</strong>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;line-height:1.4;">{{ $flowIssue['description'] }}</p>
                            </div>
                        @empty
                            <div class="mc-version-change" style="padding:.7rem;color:#15803d;background:rgba(34,197,94,.08);font-size:.72rem;">No flow issues detected.</div>
                        @endforelse
                    </div>
                </div>

                <div style="display:grid;gap:.8rem;">
                    @forelse($activityFlows as $flowIndex => $flow)
                        <div class="mc-wa-flow-row" wire:key="activity-flow-{{ $flowIndex }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.65rem;">
                                <p class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:800;">Flow {{ $flowIndex + 1 }}</p>
                                @if($canManage)
                                    <button type="button" wire:click="removeActivityFlow({{ $flowIndex }})" class="mc-wa-review-chip">Remove</button>
                                @endif
                            </div>
                            <div class="mc-wa-flow-grid">
                                <label>Activity ID<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.activity_id" @readonly(!$canManage)></label>
                                <label>Flow ID<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.flow_id" @readonly(!$canManage)></label>
                                <label>Type<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.activity_type" @readonly(!$canManage)></label>
                                <label>Group<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.group_label" @readonly(!$canManage)></label>
                                <label>Origin<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.origin_country" @readonly(!$canManage)></label>
                                <label>Destination<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.destination_country" @readonly(!$canManage)></label>
                                <label>Start date<input type="date" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.start_date" @readonly(!$canManage)></label>
                                <label>End date<input type="date" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.end_date" @readonly(!$canManage)></label>
                                <label>Duration days<input type="number" min="0" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.duration_days" @readonly(!$canManage)></label>
                                <label>Travel days<input type="number" min="0" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.travel_days" @readonly(!$canManage)></label>
                                <label>Participants<input type="number" min="0" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.participants_count" @readonly(!$canManage)></label>
                                <label>Fewer opp.<input type="number" min="0" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.fewer_opportunities_count" @readonly(!$canManage)></label>
                                <label>Leaders/support<input type="number" min="0" wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.group_leaders_count" @readonly(!$canManage)></label>
                                <label>Distance band<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.distance_band" placeholder="100-499 km" @readonly(!$canManage)></label>
                                <label>Responsible<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.responsible" @readonly(!$canManage)></label>
                                <label>Green travel
                                    <select wire:model.live="activityFlows.{{ $flowIndex }}.green_travel" @disabled(!$canManage)>
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </label>
                            </div>
                            <label style="margin-top:.55rem;">Learning output<input wire:model.live.debounce.700ms="activityFlows.{{ $flowIndex }}.learning_output" placeholder="Main output or learning result for this flow" @readonly(!$canManage)></label>
                        </div>
                    @empty
                        <div style="padding:1rem;border:1px dashed rgba(148,163,184,.3);border-radius:.75rem;text-align:center;color:#64748b;font-size:.76rem;">No flows yet. Add one manually or generate from participants.</div>
                    @endforelse
                </div>
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
