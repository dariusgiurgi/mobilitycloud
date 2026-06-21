<x-filament-panels::page>
    @php
        $sections = $this->getSections();
        $summary = $this->getApplicationSummary();
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
        .mc-wa-summary { display:grid;grid-template-columns:minmax(220px,1.5fr) repeat(3,minmax(110px,.55fr));gap:1rem;align-items:center; }
        .mc-wa-layout { display:grid;grid-template-columns:230px minmax(0,1fr);gap:1.25rem;align-items:start;margin-top:1.25rem; }
        .mc-wa-outline { position:sticky;top:1rem;padding:.9rem;border:1px solid rgba(148,163,184,.22);border-radius:.8rem;background:#fff; }
        .mc-wa-outline a { display:flex;align-items:flex-start;gap:.45rem;padding:.42rem .5rem;border-radius:.45rem;color:#64748b;text-decoration:none;font-size:.72rem;line-height:1.35; }
        .mc-wa-outline a:hover { color:#4f46e5;background:rgba(99,102,241,.07); }
        .mc-wa-section { scroll-margin-top:1rem; }
        .mc-wa-card-actions { display:flex;align-items:center;gap:.1rem; }
        .mc-wa-progress { height:7px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden; }
        .dark .mc-wa-outline { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        @media (max-width:1000px) { .mc-wa-summary { grid-template-columns:repeat(3,minmax(0,1fr)); }.mc-wa-summary-main { grid-column:1/-1; }.mc-wa-layout { grid-template-columns:1fr; }.mc-wa-outline { position:static; }.mc-wa-outline-list { display:grid;grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:650px) { .mc-wa-summary { grid-template-columns:1fr 1fr; }.mc-wa-outline-list { grid-template-columns:1fr; } }
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
                        <span wire:loading.remove wire:target="content,titles">Changes are saved automatically</span>
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
                <x-filament::button wire:click="loadTemplate" color="gray" size="sm"
                    wire:confirm="Loading a template will replace all existing sections and permanently remove any text already written. Continue?">
                    Load template
                </x-filament::button>
            @endif
            <x-filament::button tag="a" :href="route('projects.export-application', $record)" target="_blank" icon="heroicon-o-arrow-down-tray" size="sm">
                Export PDF
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section style="margin-top:1rem;">
        <div class="mc-wa-summary">
            <div class="mc-wa-summary-main">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.45rem;">
                    <span class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:650;">{{ $summary['progress'] }}% drafted</span>
                    <span class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;">{{ $summary['completed'] }} of {{ $summary['total'] }} sections</span>
                </div>
                <div class="mc-wa-progress"><div style="height:100%;width:{{ $summary['progress'] }}%;background:#6366f1;border-radius:9999px;"></div></div>
            </div>
            <div><p class="text-gray-500 dark:text-gray-400" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;">Remaining</p><p class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;">{{ $summary['remaining'] }}</p></div>
            <div><p class="text-gray-500 dark:text-gray-400" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;">Total words</p><p class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;">{{ number_format($summary['words']) }}</p></div>
            <div><p class="text-gray-500 dark:text-gray-400" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;">Over limit</p><p style="font-size:1rem;font-weight:650;color:{{ $summary['over_limit'] > 0 ? '#dc2626' : 'inherit' }};">{{ $summary['over_limit'] }}</p></div>
        </div>
    </x-filament::section>

    <div class="mc-wa-layout">
        @if($sections->isNotEmpty())
            <aside class="mc-wa-outline">
                <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.55rem;padding:0 .35rem;">
                    <span class="text-gray-950 dark:text-white" style="font-size:.73rem;font-weight:700;">Application outline</span>
                    <x-help-tip id="application-outline" title="Application outline">Use this list to jump directly to a question. A filled dot means that the section already contains text.</x-help-tip>
                </div>
                <div class="mc-wa-outline-list">
                    @foreach($categories as $category => $categorySections)
                        <div style="margin-bottom:.55rem;">
                            <p class="text-gray-400" style="font-size:.61rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.25rem .5rem;">{{ $category }}</p>
                            @foreach($categorySections as $outlineSection)
                                @php $outlineFilled = trim(strip_tags($this->content[$outlineSection->id] ?? (string) $outlineSection->content)) !== ''; @endphp
                                <a href="#application-section-{{ $outlineSection->id }}">
                                    <span style="width:6px;height:6px;flex:none;margin-top:.28rem;border-radius:9999px;background:{{ $outlineFilled ? '#22c55e' : 'rgba(148,163,184,.45)' }};"></span>
                                    <span>{{ \Illuminate\Support\Str::limit($outlineSection->title, 58) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </aside>
        @endif

        <main style="min-width:0;">

    @if($sections->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;margin:0 0 1rem;">No sections yet. Load a template (KA1/KA2) or add a free section.</p>
            @if($canManage)
                <button type="button" wire:click="addSection" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">+ Add section</button>
            @endif
        </div>
    @else
        @php $currentCat = null; @endphp
        @foreach($sections as $sec)
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

                <textarea rows="6" wire:key="content-{{ $sec->id }}"
                          wire:model.live.debounce.800ms="content.{{ $sec->id }}"
                          placeholder="Write your answer here…" @readonly(!$canManage)></textarea>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;font-size:11px;">
                    <span class="text-gray-400">{{ $words }} words</span>
                    <span style="color:{{ $over ? '#dc2626' : '#9ca3af' }};font-weight:{{ $over ? '600' : '400' }};">
                        {{ $count }}@if($limit) / {{ $limit }}@endif characters
                    </span>
                </div>
            </div>
        @endforeach

        @if($canManage)
            <button type="button" wire:click="addSection"
                    class="text-gray-500 dark:text-gray-400"
                    style="width:100%;padding:12px;border:2px dashed rgba(100,116,139,.3);border-radius:12px;background:transparent;cursor:pointer;font-size:13px;font-weight:500;">
                + Add section
            </button>
        @endif
    @endif
        </main>
    </div>

    </div>

    {{-- ── Library picker modal ── --}}
    @if($showLibrary)
        <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:3.5rem 1rem;background:rgba(0,0,0,.5);"
             wire:click.self="closeLibrary">
<div class="mc-lib-modal"
                 style="width:100%;max-width:740px;max-height:82vh;display:flex;flex-direction:column;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45);overflow:hidden;background:#ffffff;color:#18181b;">
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
