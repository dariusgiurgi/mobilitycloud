<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-search-wrap{max-width:900px;display:grid;gap:1rem}.mc-search-input{width:100%;padding:.85rem 1rem .85rem 2.8rem;border:1px solid rgba(100,116,139,.25);border-radius:.8rem;background:transparent;font-size:.95rem}.mc-search-box{position:relative}.mc-search-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:1.1rem;color:#64748b}.mc-search-results{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-result{display:flex;gap:.7rem;align-items:flex-start;padding:.72rem 0;border-top:1px solid rgba(100,116,139,.13)}.mc-result:first-child{border-top:0;padding-top:0}.mc-result:hover .mc-result-title{color:#4f46e5}@media(max-width:760px){.mc-search-results{grid-template-columns:1fr}}
    </style>

    <div class="mc-search-wrap">
        <div class="mc-search-box">
            <x-filament::icon icon="heroicon-o-magnifying-glass" class="mc-search-icon" />
            <input type="search" wire:model.live.debounce.300ms="search" class="mc-search-input text-gray-950 dark:text-white" placeholder="Search by name, email, reference or filename…" autofocus>
        </div>

        @if(mb_strlen(trim($search)) < 2)
            <x-filament::section>
                <div style="text-align:center;padding:2rem 1rem;color:#64748b;font-size:.82rem;">Enter at least two characters to search across this workspace.</div>
            </x-filament::section>
        @elseif($this->resultCount === 0)
            <x-filament::section>
                <div style="text-align:center;padding:2rem 1rem;color:#64748b;font-size:.82rem;">No accessible records match “{{ $search }}”.</div>
            </x-filament::section>
        @else
            <p class="text-gray-500 dark:text-gray-400" style="font-size:.75rem;">{{ $this->resultCount }} {{ str('result')->plural($this->resultCount) }}</p>
            <div class="mc-search-results">
                @foreach(['projects' => ['Projects','heroicon-o-rectangle-stack'], 'participants' => ['Participants','heroicon-o-users'], 'expenses' => ['Expenses','heroicon-o-banknotes'], 'documents' => ['Documents','heroicon-o-document-text']] as $group => [$label,$icon])
                    @if($this->results[$group]->isNotEmpty())
                        <x-filament::section :heading="$label" :icon="$icon">
                            @foreach($this->results[$group] as $result)
                                <a href="{{ $result['url'] }}" class="mc-result">
                                    <div style="min-width:0;">
                                        <div class="mc-result-title text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:650;overflow-wrap:anywhere;">{{ $result['title'] }}</div>
                                        <div class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;margin-top:.15rem;overflow-wrap:anywhere;">{{ $result['detail'] }}</div>
                                    </div>
                                </a>
                            @endforeach
                        </x-filament::section>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
