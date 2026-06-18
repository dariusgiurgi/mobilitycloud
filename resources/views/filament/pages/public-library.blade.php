<x-filament-panels::page>
    @php
        $blocks = $this->getBlocks();
        $official = \App\Models\PublicContentBlock::OFFICIAL_EMAIL;
        $me = auth()->user();
        $activeFilters = $this->activeFilterCount();
    @endphp

    <div class="mc-pub">

    {{-- ── Toolbar: search + sort + filters button ── --}}
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem;">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search blocks…"
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
                <input type="checkbox" wire:model.live="verifiedOnly" value="1" style="accent-color:#16a34a;">
                Verified only
            </label>

            <label class="text-gray-700 dark:text-gray-200"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;font-size:13px;cursor:pointer;">
                <input type="checkbox" wire:model.live="officialOnly" value="1" style="accent-color:#6366f1;">
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

    {{-- ── Cards grid ── --}}
    @if($blocks->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;margin:0;">No blocks match your search.</p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
            @foreach($blocks as $b)
                @php
                    $isOfficial = $b->author && $b->author->email === $official;
                    $liked = $me && $b->likes()->where('user_id', $me->id)->exists();
                    $stripe = $isOfficial ? '#6366f1' : ($b->is_proven ? '#16a34a' : '#94a3b8');
                @endphp

                <div wire:key="pub-{{ $b->id }}"
                     class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:.75rem;border-top:3px solid {{ $stripe }};">

                    {{-- Badges --}}
                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                        <x-filament::badge size="sm" color="primary">{{ $b->categoryLabel() }}</x-filament::badge>
                        <x-filament::badge size="sm" color="gray">{{ strtoupper($b->ka_action) }}</x-filament::badge>
                        @if($b->is_proven)
                            <x-filament::badge size="sm" color="success">Verified</x-filament::badge>
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
                        by {{ $b->author->name ?? 'Unknown' }}@if($b->source_note) · {{ \Illuminate\Support\Str::limit($b->source_note, 40) }}@endif
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

                        {{-- Import --}}
                        <button type="button" wire:click="import({{ $b->id }})"
                                style="border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;font-weight:600;padding:6px 12px;border-radius:7px;">
                            Import
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    </div>

    {{-- ── Preview modal ── --}}
    @php $preview = $this->getPreviewBlock(); @endphp
    @if($preview)
        <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:3.5rem 1rem;background:rgba(0,0,0,.55);"
             wire:click.self="closePreview">
            <div class="mc-pub-modal"
                 style="width:100%;max-width:680px;max-height:82vh;display:flex;flex-direction:column;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45);overflow:hidden;background:#ffffff;color:#18181b;">

                <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.2);">
                    <div style="font-size:15px;font-weight:600;">{{ $preview->title }}</div>
                    <button type="button" wire:click="closePreview" style="border:none;background:transparent;cursor:pointer;color:#9ca3af;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div style="padding:.75rem 1.25rem;border-bottom:1px solid rgba(100,116,139,.12);display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                    <x-filament::badge size="sm" color="primary">{{ $preview->categoryLabel() }}</x-filament::badge>
                    <x-filament::badge size="sm" color="gray">{{ strtoupper($preview->ka_action) }}</x-filament::badge>
                    @if($preview->is_proven)<x-filament::badge size="sm" color="success">Verified</x-filament::badge>@endif
                    @if($preview->author && $preview->author->email === $official)<x-filament::badge size="sm" color="info">Official</x-filament::badge>@endif
                    <span style="margin-left:auto;font-size:11px;color:#9ca3af;">by {{ $preview->author->name ?? 'Unknown' }}</span>
                </div>

                <div style="overflow:auto;padding:1.25rem;font-size:13px;line-height:1.6;white-space:pre-wrap;">{{ $preview->body }}</div>

                @if($preview->source_note)
                    <div style="padding:.5rem 1.25rem;font-size:11px;color:#9ca3af;border-top:1px solid rgba(100,116,139,.12);">Source: {{ $preview->source_note }}</div>
                @endif

                <div style="padding:.85rem 1.25rem;border-top:1px solid rgba(100,116,139,.2);display:flex;justify-content:flex-end;gap:.5rem;">
                    <button type="button" wire:click="closePreview"
                            style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Close</button>
                    <button type="button" wire:click="import({{ $preview->id }})"
                            style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Import to my library</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Report modal ── --}}
    @if($reportingId)
        @php $reportReasons = $this->getReportReasons(); @endphp
        <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(0,0,0,.55);"
             wire:click.self="closeReport">
            <div class="mc-pub-modal"
                 style="width:100%;max-width:440px;border-radius:14px;padding:1.5rem;box-shadow:0 20px 50px rgba(0,0,0,.4);background:#ffffff;color:#18181b;">

                <h3 style="font-size:16px;font-weight:600;margin:0 0 1rem;">Report this block</h3>

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

                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                    <button type="button" wire:click="closeReport"
                            style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                    <button type="button" wire:click="submitReport"
                            style="padding:8px 16px;border-radius:8px;border:none;background:#dc2626;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Submit report</button>
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