<x-filament-panels::page>
    <x-ui-polish />
    @php
        $categories = $this->archiveCategories();
        $selected = $this->selectedCount();
        $exportsLocked = $record->exportsLockedUntilPayment();
        $canConfigure = $this->canConfigureArchive();
    @endphp

    <style>
        .mc-final-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;margin-top:1rem; }
        .mc-final-item { width:100%;min-height:128px;padding:.85rem;text-align:left;border:1px solid rgba(148,163,184,.24);border-radius:.8rem;background:#fff;transition:border-color .15s,box-shadow .15s,transform .15s; }
        .mc-final-item:not(:disabled) { cursor:pointer; }
        .mc-final-item:not(:disabled):hover { transform:translateY(-1px);border-color:rgba(99,102,241,.52);box-shadow:0 9px 24px rgba(15,23,42,.07); }
        .mc-final-item.is-selected { border-color:rgba(79,70,229,.65);background:linear-gradient(135deg,rgba(99,102,241,.10),rgba(14,165,233,.045)); }
        .mc-final-check { width:23px;height:23px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;border:1px solid rgba(100,116,139,.32);color:transparent;float:right; }
        .mc-final-item.is-selected .mc-final-check { background:#4f46e5;border-color:#4f46e5;color:#fff; }
        .dark .mc-final-item { background:rgb(17,24,39);border-color:rgba(255,255,255,.11); }
        .dark .mc-final-item.is-selected { background:linear-gradient(135deg,rgba(99,102,241,.22),rgba(14,165,233,.09)); }
        @media (max-width:1000px) { .mc-final-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:640px) { .mc-final-grid { grid-template-columns:1fr; } }
    </style>

    <x-filament::section>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="max-width:720px;">
                <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:750;margin:0;">Final project archive</h2>
                    <x-filament::badge color="gray">{{ $selected }} selected</x-filament::badge>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.83rem;line-height:1.5;margin:.35rem 0 0;">
                    Choose the project material that belongs in the handover archive. Your choice saves automatically and never removes data from the project.
                </p>
            </div>
            @if ($exportsLocked)
                <x-filament::badge color="warning" icon="heroicon-m-lock-closed">Final export unlocks after payment</x-filament::badge>
            @elseif ($selected)
                <x-filament::button tag="a" :href="route('projects.final-archive', $record)" icon="heroicon-m-archive-box-arrow-down">
                    Download final ZIP
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @if ($exportsLocked)
        <x-filament::section style="margin-top:1rem;">
            <div style="display:flex;gap:.75rem;align-items:flex-start;">
                <x-filament::icon icon="heroicon-o-lock-closed" style="width:1.25rem;height:1.25rem;color:#d97706;flex:none;margin-top:.1rem;" />
                <div>
                    <h3 class="text-gray-950 dark:text-white" style="font-size:.9rem;font-weight:700;margin:0;">Archive download is temporarily locked</h3>
                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;line-height:1.45;margin:.18rem 0 0;">The selection remains available for review. The ZIP becomes downloadable once the invoice is confirmed as paid.</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    <div class="mc-final-grid">
        @foreach ($categories as $key => $category)
            @php($isSelected = (bool) ($include[$key] ?? false))
            <button
                type="button"
                class="mc-final-item {{ $isSelected ? 'is-selected' : '' }}"
                @if ($canConfigure) wire:click="toggleArchiveCategory('{{ $key }}')" @else disabled @endif
            >
                <span class="mc-final-check">✓</span>
                <div class="text-gray-950 dark:text-white" style="font-size:.84rem;font-weight:750;padding-right:1.8rem;line-height:1.35;">{{ $category['label'] }}</div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;line-height:1.45;margin:.35rem 0 0;">{{ $category['detail'] }}</p>
                <div class="text-gray-400 dark:text-gray-500" style="font-size:.67rem;font-weight:650;margin-top:.55rem;">{{ $category['count'] }} {{ $category['count'] === 1 ? 'record' : 'records' }}</div>
            </button>
        @endforeach
    </div>

    @if (! $canConfigure)
        <p class="text-gray-500 dark:text-gray-400" style="font-size:.75rem;margin-top:.8rem;">Only the project owner and editors can change the archive selection.</p>
    @endif

    @if (! $selected)
        <x-filament::section style="margin-top:1rem;">
            <p class="text-gray-600 dark:text-gray-300" style="font-size:.82rem;margin:0;">Select at least one category to enable the final ZIP download.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
