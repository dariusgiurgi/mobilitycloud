<x-filament-panels::page>
    @php $sections = $this->getSections(); @endphp

    <style>
        .mc-wa textarea { width:100%; background:transparent; border:1px solid rgba(100,116,139,.25); border-radius:8px; padding:10px 12px; font-size:13px; line-height:1.6; resize:vertical; color:inherit; }
        .mc-wa input.mc-title { width:100%; background:transparent; border:none; font-size:15px; font-weight:600; color:inherit; padding:2px 0; }
        .mc-wa select option { background:#fff; }
        .dark .mc-wa select option { background:#27303f; }
        .mc-iconbtn { flex-shrink:0; width:28px; height:28px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:#9ca3af; display:inline-flex; align-items:center; justify-content:center; }
        .mc-libcard { border:1px solid rgba(100,116,139,.2); border-radius:10px; padding:.85rem 1rem; margin-bottom:.6rem; }
        .mc-libcard:hover { border-color:#6366f1; }
    </style>

    <div class="mc-wa">

    {{-- Toolbar --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <a href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('board', ['record' => $record]) }}"
           class="text-gray-700 dark:text-gray-200"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid rgba(100,116,139,.3);text-decoration:none;font-size:13px;">
            ← Budget board
        </a>
        <div style="flex:1;"></div>
        <select wire:model="selectedTemplate" class="text-gray-950 dark:text-white"
                style="padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;">
            @foreach($this->getTemplates() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="loadTemplate"
                @if($this->hasContent) wire:confirm="Loading a template will replace all existing sections and you will lose any text you have written. Are you sure?" @endif
                style="padding:8px 14px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;font-weight:500;" class="text-gray-700 dark:text-gray-200">
            Load template
        </button>
        <a href="{{ route('projects.export-application', $record) }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:none;background:#6366f1;color:#fff;text-decoration:none;font-size:13px;font-weight:500;">
            Export
        </a>
    </div>

    @if($sections->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;margin:0 0 1rem;">No sections yet. Load a template (KA1/KA2) or add a free section.</p>
            <button type="button" wire:click="addSection" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">+ Add section</button>
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

            <div wire:key="section-{{ $sec->id }}" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.1rem 1.25rem;margin-bottom:1rem;">
                <div style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.6rem;">
                    <input type="text" wire:key="title-{{ $sec->id }}" class="mc-title text-gray-950 dark:text-white"
                           wire:model.blur="titles.{{ $sec->id }}">

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

                <textarea rows="6" wire:key="content-{{ $sec->id }}"
                          wire:model.live.debounce.800ms="content.{{ $sec->id }}"
                          placeholder="Write your answer here…"></textarea>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;font-size:11px;">
                    <span class="text-gray-400">{{ $words }} words</span>
                    <span style="color:{{ $over ? '#dc2626' : '#9ca3af' }};font-weight:{{ $over ? '600' : '400' }};">
                        {{ $count }}@if($limit) / {{ $limit }}@endif characters
                    </span>
                </div>
            </div>
        @endforeach

        <button type="button" wire:click="addSection"
                class="text-gray-500 dark:text-gray-400"
                style="width:100%;padding:12px;border:2px dashed rgba(100,116,139,.3);border-radius:12px;background:transparent;cursor:pointer;font-size:13px;font-weight:500;">
            + Add section
        </button>
    @endif

    </div>

    {{-- ── Library picker modal ── --}}
    @if($showLibrary)
        <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:3.5rem 1rem;background:rgba(0,0,0,.5);"
             wire:click.self="closeLibrary">
            <div class="bg-white dark:bg-gray-900 text-gray-950 dark:text-white"
                 style="width:100%;max-width:740px;max-height:82vh;display:flex;flex-direction:column;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45);overflow:hidden;">

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
                            No blocks found. Add some in <strong>Tools → Content Library</strong>.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
