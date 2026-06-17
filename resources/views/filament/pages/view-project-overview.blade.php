{{-- Project Overview hub. Uses Filament components + inline-style layout so it
     renders correctly regardless of the app Tailwind build. --}}
<x-filament-panels::page>
    @php
        $status = $this->getStatusEnum();
        $transitions = $status->allowedTransitions();
        $sections = $this->getSectionCount();
        $baskets = $this->record->budget_lines_count ?? $this->record->budgetLines->count();
        $approved = (float) $this->record->approved_budget;
        $total = (float) $this->record->total_budget;
        $partners = $this->record->partners;
        $ka = $this->record->ka_action;
        $kaLabel = $ka ? (\App\Support\ApplicationTemplates::list()[$ka] ?? strtoupper($ka)) : null;
        $eur = fn ($v) => number_format((float) $v, 2, '.', ',') . ' €';
    @endphp

    {{-- Status + lifecycle transitions --}}
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <span style="opacity:.65;font-size:.875rem;">Status</span>
                <x-filament::badge :color="$status->getColor()" size="lg">{{ $status->getLabel() }}</x-filament::badge>
                @if ($kaLabel)
                    <x-filament::badge color="gray">{{ strtoupper($ka) }}</x-filament::badge>
                @endif
                @if ($this->record->is_activated)
                    <x-filament::badge color="success">Activated</x-filament::badge>
                @endif
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                @forelse ($transitions as $next)
                    <x-filament::button
                        wire:click="transitionTo('{{ $next->value }}')"
                        wire:confirm="Move this project to {{ $next->getLabel() }}?"
                        :color="$next->getColor()"
                        size="sm"
                    >
                        Mark as {{ $next->getLabel() }}
                    </x-filament::button>
                @empty
                    <span style="opacity:.65;font-size:.875rem;">No further transitions.</span>
                @endforelse
            </div>
        </div>
    </x-filament::section>

    {{-- Budget snapshot (measured against the approved grant when confirmed) --}}
    <x-filament::section heading="Budget">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1.25rem;">
            <div>
                <div style="opacity:.65;font-size:.8125rem;">Grant</div>
                <div style="font-size:1.5rem;font-weight:600;line-height:1.2;">{{ $eur($this->record->effective_budget) }}</div>
                @if ($approved > 0 && abs($approved - $total) > 0.001)
                    <div style="opacity:.55;font-size:.75rem;margin-top:.15rem;">Requested: {{ $eur($total) }}</div>
                @endif
            </div>
            <div>
                <div style="opacity:.65;font-size:.8125rem;">Spent</div>
                <div style="font-size:1.5rem;font-weight:600;line-height:1.2;">{{ $eur($this->record->spent) }}</div>
            </div>
            <div>
                <div style="opacity:.65;font-size:.8125rem;">Remaining</div>
                <div style="font-size:1.5rem;font-weight:600;line-height:1.2;">{{ $eur($this->record->remaining) }}</div>
            </div>
            <div>
                <div style="opacity:.65;font-size:.8125rem;">Progress</div>
                <div style="font-size:1.5rem;font-weight:600;line-height:1.2;">{{ $this->record->progress }}%</div>
                <div style="margin-top:.5rem;height:6px;border-radius:9999px;background:rgba(120,120,120,.2);overflow:hidden;">
                    <div style="height:100%;border-radius:9999px;background:#6366f1;width:{{ $this->record->progress }}%;"></div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Details --}}
    <x-filament::section heading="Details">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;font-size:.875rem;">
            <div>
                <div style="opacity:.65;">Action</div>
                <div style="font-weight:500;">{{ $kaLabel ?: '—' }}</div>
            </div>
            <div>
                <div style="opacity:.65;">Grant ref</div>
                <div style="font-weight:500;">{{ $this->record->grant_ref ?: '—' }}</div>
            </div>
            <div>
                <div style="opacity:.65;">Partner organisations</div>
                @if (count($partners) > 0)
                    <div style="display:flex;flex-direction:column;gap:.15rem;">
                        @foreach ($partners as $p)
                            <div style="font-weight:500;">
                                {{ $p['name'] }}@if (! empty($p['country'])) <span style="opacity:.6;font-weight:400;">({{ $p['country'] }})</span>@endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="font-weight:500;">—</div>
                @endif
            </div>
            <div>
                <div style="opacity:.65;">Dates</div>
                <div style="font-weight:500;">
                    {{ $this->record->start_date?->format('d M Y') ?? '—' }} → {{ $this->record->end_date?->format('d M Y') ?? '—' }}
                </div>
            </div>
            <div>
                <div style="opacity:.65;">Application</div>
                <div style="font-weight:500;">{{ $sections }} {{ \Illuminate\Support\Str::plural('section', $sections) }}</div>
            </div>
            <div>
                <div style="opacity:.65;">Budget baskets</div>
                <div style="font-weight:500;">{{ $baskets }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
