{{-- Budget estimate (Writing stage). Pre-fills the grant + baskets at approval. --}}
<x-filament-panels::page>
    @php
        $bands = $this->getBands();
        $eur = fn ($v) => number_format((float) $v, 2, '.', ',') . ' €';
        $inStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;';
    @endphp

    <style>
        .mc-est label.f { display:block; font-size:.75rem; opacity:.65; margin-bottom:.3rem; }
        .mc-est .fl { display:flex;align-items:center;gap:.35rem;margin-bottom:.3rem; }
        .mc-est .fl label.f { margin-bottom:0; }
        .mc-est input[type=number], .mc-est select { color:inherit; }
        .mc-est select option { background:#fff; } .dark .mc-est select option { background:#27303f; }
        .mc-est .row { display:flex; align-items:center; gap:.5rem; font-size:13px; }
    </style>

    <div class="mc-est">
    <x-filament::section heading="Inputs">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;">
            <div>
                <div class="fl">
                    <label class="f">Persons (IS + travel)</label>
                    <x-help-tip id="estimate-is-persons" title="Persons for IS and travel">
                        People eligible for Individual Support and the travel unit contribution. This can differ from the Organisational Support participant count under some action rules.
                    </x-help-tip>
                </div>
                <input type="number" min="0" wire:model.live.debounce.500ms="persons" style="{{ $inStyle }}">
            </div>
            <div>
                <div class="fl">
                    <label class="f">Participants (OS)</label>
                    <x-help-tip id="estimate-os-participants" title="Participants for Organisational Support">
                        Enter the number of people who generate an Organisational Support unit contribution under the applicable programme rules.
                    </x-help-tip>
                </div>
                <input type="number" min="0" wire:model.live.debounce.500ms="participants" style="{{ $inStyle }}">
            </div>
            <div>
                <label class="f">Activity days</label>
                <input type="number" min="0" wire:model.live.debounce.500ms="days" style="{{ $inStyle }}">
            </div>
            <div>
                <label class="f">Travel days</label>
                <input type="number" min="0" wire:model.live.debounce.500ms="travelDays" style="{{ $inStyle }}">
            </div>
            <div>
                <label class="f">IS rate (€/person/day)</label>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="isRate" style="{{ $inStyle }}">
            </div>
            <div>
                <label class="f">Distance band</label>
                <select wire:model.live="travelBandIndex" class="text-gray-950 dark:text-white" style="{{ $inStyle }}">
                    @foreach($bands as $i => $b)
                        <option value="{{ $i }}">{{ $b['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="f">OS rate (€/participant)</label>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="osRate" style="{{ $inStyle }}">
            </div>
            <div>
                <div class="fl">
                    <label class="f">Inclusion support — organisations (€)</label>
                    <x-help-tip id="estimate-inclusion-support" title="Inclusion Support for organisations">
                        Enter the approved unit contribution or total intended to help the organisation arrange participation for people with fewer opportunities. Participant-specific real costs are handled separately when applicable.
                    </x-help-tip>
                </div>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="inclusionOrgTotal" style="{{ $inStyle }}">
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-top:1rem;">
            <label class="row"><input type="checkbox" wire:model.live="greenTravel"> Green travel</label>
            <label class="row"><input type="checkbox" wire:model.live="includeTravelDaysInIS"> Include travel days in IS</label>
            <label class="row"><input type="checkbox" wire:model.live="includeOS"> Include Organisational Support</label>
        </div>
    </x-filament::section>

    <x-filament::section heading="Estimated grant">
        @php
            $rows = [
                ['Travel', $this->travelTotal, $this->persons . ' × ' . $eur($this->travelPerPerson)],
                ['Individual Support', $this->isTotal, $this->persons . ' × ' . ($this->days + ($this->includeTravelDaysInIS ? $this->travelDays : 0)) . ' days × ' . $eur($this->isRate)],
                ['Organisational Support', $this->osTotal, $this->includeOS ? ($this->participants . ' × ' . $eur($this->osRate)) : 'excluded'],
                ['Inclusion Support', $this->inclusionTotal, 'organisations'],
            ];
        @endphp

        <div style="display:flex;flex-direction:column;gap:.1rem;">
            @foreach($rows as [$label, $val, $detail])
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;padding:.55rem 0;border-bottom:1px solid rgba(100,116,139,.12);">
                    <div>
                        <span style="font-weight:500;font-size:.9rem;">{{ $label }}</span>
                        <span style="opacity:.55;font-size:.75rem;margin-left:.5rem;">{{ $detail }}</span>
                    </div>
                    <span style="font-weight:600;font-size:.95rem;white-space:nowrap;">{{ $eur($val) }}</span>
                </div>
            @endforeach

            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;padding:.8rem 0 0;">
                <span style="font-weight:700;font-size:1rem;">Total grant</span>
                <span style="font-weight:700;font-size:1.4rem;">{{ $eur($this->grandTotal) }}</span>
            </div>
        </div>

        <div style="margin-top:1rem;padding:.7rem .9rem;border-radius:8px;background:rgba(99,102,241,.08);font-size:.8rem;line-height:1.5;">
            These figures are saved automatically and pre-fill the grant and budget baskets when you mark the project as <strong>Approved</strong> from the Overview tab. You can adjust the confirmed grant afterwards in Settings.
        </div>
    </x-filament::section>
    </div>
</x-filament-panels::page>
