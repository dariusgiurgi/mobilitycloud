<x-filament-panels::page>
    <x-ui-polish />
    @php
        $blocks = $this->getBlocks();
        $me = auth()->user();
        $activeFilters = $this->activeFilterCount();
        $importedBlocks = $this->getImportedBlocks();
        $canManage = filled($me);
    @endphp

    <div class="mc-pub">

    <x-filament::section>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="max-width:720px;">
                <div style="display:flex;align-items:center;gap:.4rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">Shared knowledge, reusable safely</h2>
                    <x-help-tip id="public-library-import" title="Importing a block">
                        Import creates a private, editable copy in your Content Library. The public original remains unchanged, and later public updates do not overwrite your copy.
                    </x-help-tip>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;line-height:1.5;margin-top:.25rem;">Preview the full text and source before importing. Adapt every block to the project you are writing.</p>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem;">
                <x-filament::badge color="success" size="sm">Proven</x-filament::badge>
                <x-filament::badge color="info" size="sm">Official</x-filament::badge>
                <x-help-tip id="public-library-trust" title="Proven and official content">
                    Proven means the author linked the text to an approved application and supplied a source. Official means the block was published by MobilityCloud. Neither label removes the need to verify and adapt the content.
                </x-help-tip>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Toolbar: search + sort + filters button ── --}}
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin:1rem 0;">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search blocks…"
               aria-label="Search public content"
               class="mc-pub-input text-gray-950 dark:text-white"
               style="flex:1;min-width:200px;padding:9px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;">

        <select wire:model.live="sort" class="mc-pub-input text-gray-950 dark:text-white"
                style="padding:9px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;">
            <option value="relevant">Most relevant</option>
            <option value="new">Newest</option>
            <option value="imports">Most imported</option>
        </select>

        <button type="button" wire:click="$toggle('showFilters')"
                class="text-gray-700 dark:text-gray-200"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:{{ $showFilters ? 'rgba(99,102,241,.08)' : 'transparent' }};cursor:pointer;font-size:13px;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            Filters
            @if($activeFilters > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#6366f1;color:#fff;font-size:11px;font-weight:700;">{{ $activeFilters }}</span>
            @endif
        </button>
    </div>

    {{-- ── Collapsible filters panel ── --}}
    @if($showFilters)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
         style="padding:1.1rem 1.25rem;margin-bottom:1.25rem;">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">

            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Category</label>
                <select wire:model.live="category" class="mc-pub-input text-gray-950 dark:text-white"
                        style="padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;min-width:160px;">
                    <option value="">All categories</option>
                    @foreach($this->getCategories() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Action</label>
                <select wire:model.live="kaAction" class="mc-pub-input text-gray-950 dark:text-white"
                        style="padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;min-width:140px;">
                    <option value="">All actions</option>
                    @foreach($this->getKaActions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Language</label>
                <select wire:model.live="language" class="mc-pub-input text-gray-950 dark:text-white"
                        style="padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;min-width:140px;">
                    <option value="">All languages</option>
                    @foreach($this->getLanguages() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <label class="text-gray-700 dark:text-gray-200"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" wire:model.live="provenOnly" style="accent-color:#16a34a;">
                Proven only
            </label>

            <label class="text-gray-700 dark:text-gray-200"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" wire:model.live="officialOnly" style="accent-color:#6366f1;">
                Official only
            </label>

            <div style="flex:1;"></div>

            <button type="button" wire:click="clearFilters"
                    class="text-gray-500 dark:text-gray-400"
                    style="padding:8px 14px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;cursor:pointer;font-size:13px;">
                Clear all
            </button>
        </div>
    </div>
    @endif

    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.65rem;">
        <span class="text-gray-500 dark:text-gray-400" style="font-size:.76rem;">{{ $blocks->count() }} {{ str('block')->plural($blocks->count()) }} shown</span>
    </div>

    {{-- ── Cards grid ── --}}
    @if($blocks->isEmpty())
        <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <x-filament::icon icon="heroicon-o-magnifying-glass" class="mx-auto h-8 w-8 text-gray-400" />
            <p class="text-gray-950 dark:text-white" style="font-size:.9rem;font-weight:600;margin-top:.65rem;">No matching content</p>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;margin-top:.2rem;">Try a broader search or clear the active filters.</p>
            @if ($activeFilters > 0 || filled($search))
                <x-filament::button wire:click="clearFilters" color="gray" size="sm" style="margin-top:.8rem;">Clear filters</x-filament::button>
            @endif
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
            @foreach($blocks as $b)
                @php
                    $isOfficial = $b->isOfficial();
                    $liked = $b->likes->isNotEmpty();
                    $localBlock = $importedBlocks->get($b->id);
                    $stripe = $isOfficial ? '#6366f1' : ($b->is_proven ? '#16a34a' : '#94a3b8');
                @endphp

                <div wire:key="pub-{{ $b->id }}"
                     class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:.75rem;border-top:3px solid {{ $stripe }};">

                    {{-- Badges --}}
                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                        <x-filament::badge size="sm" color="primary">{{ $b->categoryLabel() }}</x-filament::badge>
                            <x-filament::badge size="sm" color="gray">{{ $b->ka_action === 'any' ? 'Any action' : strtoupper($b->ka_action) }}</x-filament::badge>
                            <x-filament::badge size="sm" color="gray">{{ strtoupper($b->language) }}</x-filament::badge>
                        @if($b->is_proven)
                            <x-filament::badge size="sm" color="success">Proven</x-filament::badge>
                        @endif
                        @if($isOfficial)
                            <x-filament::badge size="sm" color="info">Official</x-filament::badge>
                        @endif
                    </div>

                    {{-- Title --}}
                    <div class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;line-height:1.35;">
                        {{ $b->title }}
                    </div>

                    {{-- Preview text --}}
                    <div class="text-gray-500 dark:text-gray-400" style="font-size:12px;line-height:1.5;flex:1;">
                        {{ \Illuminate\Support\Str::limit(strip_tags($b->body), 160) }}
                    </div>

                    {{-- Author + source --}}
                    <div class="text-gray-400" style="font-size:11px;">
                        by {{ $b->displayAuthorName() }}@if($b->source_note) · {{ \Illuminate\Support\Str::limit($b->source_note, 40) }}@endif
                    </div>

                    {{-- Footer: stats + actions --}}
                    <div style="display:flex;align-items:center;gap:.5rem;border-top:1px solid rgba(100,116,139,.12);padding-top:.65rem;">
                        {{-- Like --}}
                        <button type="button" wire:click="toggleLike({{ $b->id }})"
                                title="Like"
                                style="display:inline-flex;align-items:center;gap:5px;border:none;background:transparent;cursor:pointer;font-size:12px;font-weight:600;color:{{ $liked ? '#dc2626' : '#9ca3af' }};">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="{{ $liked ? '#dc2626' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path></svg>
                            {{ $b->likes_count }}
                        </button>

                        {{-- Imports count --}}
                        <span class="text-gray-400" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
                            {{ $b->import_count }}
                        </span>

                        <div style="flex:1;"></div>

                        {{-- Report (nu pe blocul propriu) --}}
                        @if($me && $b->user_id !== $me->id)
                            <button type="button" wire:click="openReport({{ $b->id }})"
                                    title="Report this block"
                                    style="border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;color:#9ca3af;font-size:12px;padding:5px 8px;border-radius:7px;display:inline-flex;align-items:center;"
                                    onmouseover="this.style.color='#dc2626';this.style.borderColor='rgba(239,68,68,.4)';"
                                    onmouseout="this.style.color='#9ca3af';this.style.borderColor='rgba(100,116,139,.3)';">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            </button>
                        @endif

                        {{-- Edit (doar autorul) --}}
                        @if($me && $b->user_id === $me->id)
                            <a href="{{ \App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource::getUrl('edit', ['record' => $b->id]) }}"
                               class="text-gray-500 dark:text-gray-300"
                               style="border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:12px;padding:5px 10px;border-radius:7px;text-decoration:none;">
                                Edit
                            </a>
                        @endif

                        {{-- Preview --}}
                        <button type="button" wire:click="openPreview({{ $b->id }})"
                                class="text-gray-500 dark:text-gray-300"
                                style="border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:12px;padding:5px 10px;border-radius:7px;">
                            View
                        </button>

                        @if ($localBlock)
                            <a href="{{ \App\Filament\Resources\ContentBlocks\ContentBlockResource::getUrl('edit', ['record' => $localBlock]) }}"
                               style="display:inline-flex;align-items:center;gap:.3rem;border:1px solid rgba(22,163,74,.35);background:rgba(22,163,74,.08);color:#15803d;font-size:12px;font-weight:600;padding:5px 10px;border-radius:7px;text-decoration:none;">
                                <x-filament::icon icon="heroicon-m-check" style="width:.85rem;height:.85rem;" />
                                In my library
                            </a>
                        @elseif ($canManage)
                            <button type="button" wire:click="import({{ $b->id }})" wire:loading.attr="disabled" wire:target="import({{ $b->id }})"
                                    style="border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;font-weight:600;padding:6px 12px;border-radius:7px;">
                                Import copy
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    </div>

    {{-- ── Preview modal ── --}}
    @php $preview = $this->getPreviewBlock(); @endphp
    @if($preview)
        <div class="mc-modal-backdrop mc-modal-top"
             wire:click.self="closePreview">
            <div class="mc-pub-modal mc-modal-panel mc-modal-panel-wide">

                <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.2);">
                    <div style="font-size:15px;font-weight:600;">{{ $preview->title }}</div>
                    <button type="button" wire:click="closePreview" style="border:none;background:transparent;cursor:pointer;color:#9ca3af;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div style="padding:.75rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.12);display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                    <x-filament::badge size="sm" color="primary">{{ $preview->categoryLabel() }}</x-filament::badge>
                    <x-filament::badge size="sm" color="gray">{{ strtoupper($preview->ka_action) }}</x-filament::badge>
                    @if($preview->is_proven)<x-filament::badge size="sm" color="success">Proven</x-filament::badge>@endif
                    @if($preview->isOfficial())<x-filament::badge size="sm" color="info">Official</x-filament::badge>@endif
                    <span style="margin-left:auto;font-size:11px;color:#9ca3af;">by {{ $preview->displayAuthorName() }}</span>
                </div>

                <div class="mc-modal-body" style="font-size:13px;line-height:1.6;white-space:pre-wrap;">{{ $preview->body }}</div>

                @if($preview->source_note)
                    <div style="padding:.5rem 1.25rem;font-size:11px;color:#9ca3af;border-top:1px solid rgba(100,116,139,.12);">Source: {{ $preview->source_note }}</div>
                @endif

                <div class="mc-modal-actions">
                    <button type="button" wire:click="closePreview"
                            style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Close</button>
                    @if ($previewLocal = $importedBlocks->get($preview->id))
                        <x-filament::button tag="a" :href="\App\Filament\Resources\ContentBlocks\ContentBlockResource::getUrl('edit', ['record' => $previewLocal])" color="success" size="sm" icon="heroicon-m-check">
                            Open my copy
                        </x-filament::button>
                    @elseif ($canManage)
                        <x-filament::button wire:click="import({{ $preview->id }})" size="sm">
                            Import editable copy
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ── Report modal ── --}}
    @if($reportingId)
        @php $reportReasons = $this->getReportReasons(); @endphp
        <div class="mc-modal-backdrop"
             wire:click.self="closeReport">
            <div class="mc-pub-modal mc-modal-panel" style="max-width:440px;">
                <div class="mc-modal-body">

                <h3 class="mc-modal-heading">Report this block</h3>
                <p class="mc-modal-description">Tell us what should be reviewed. Your report helps keep the shared library reliable.</p>

                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:6px;color:#71717a;">Reason</label>
                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:1rem;">
                    @foreach($reportReasons as $key => $label)
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                            <input type="radio" wire:model="reportReason" value="{{ $key }}" style="accent-color:#6366f1;">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('reportReason') <p style="color:#dc2626;font-size:12px;margin:-6px 0 12px;">{{ $message }}</p> @enderror

                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:6px;color:#71717a;">Details (optional)</label>
                <textarea wire:model="reportDetails" rows="3" placeholder="Add any details…"
                          class="mc-pub-input"
                          style="width:100%;padding:9px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;font-size:13px;resize:vertical;margin-bottom:1.25rem;background:#fafafa;color:#18181b;"></textarea>

                <div class="mc-modal-actions">
                    <button type="button" wire:click="closeReport"
                            style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                    <button type="button" wire:click="submitReport"
                            style="padding:8px 16px;border-radius:8px;border:none;background:#dc2626;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Submit report</button>
                </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .dark .mc-pub-modal { background:#18212f !important; color:#f4f4f5 !important; }
        .dark .mc-pub-modal textarea { background:#27303f !important; color:#f4f4f5 !important; }
        .mc-pub-input option { background:#fff; }
        .dark .mc-pub-input option { background:#27303f; color:#f4f4f5; }
    </style>
</x-filament-panels::page>
