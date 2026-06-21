{{-- Budget estimate (Writing stage). Pre-fills the grant + baskets at approval. --}}
<x-filament-panels::page>
    @php
        $bands = $this->getBands();
        $eur = fn ($v) => number_format((float) $v, 2, '.', ',') . ' €';
        $inStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:13px;';
        $canManage = $record->canBeManagedBy(auth()->user());
    @endphp

    <style>
        .mc-est label.f { display:block; font-size:.75rem; opacity:.65; margin-bottom:.3rem; }
        .mc-est .fl { display:flex;align-items:center;gap:.35rem;margin-bottom:.3rem; }
        .mc-est .fl label.f { margin-bottom:0; }
        .mc-est input[type=number], .mc-est select { color:inherit; }
        .mc-est select option { background:#fff; } .dark .mc-est select option { background:#27303f; }
        .mc-est .row { display:flex; align-items:center; gap:.5rem; font-size:13px; }
        .mc-est-grid { display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:1rem;align-items:start;margin-top:1rem; }
        .mc-est-total { padding:1rem;border-radius:.75rem;background:rgba(99,102,241,.08);margin-top:1rem; }
        @media (max-width:1000px) { .mc-est-grid { grid-template-columns:1fr; } }
    </style>

    <div class="mc-est">
    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:.45rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">Grant estimator</h2>
                    <x-help-tip id="grant-estimator-scope" title="Planning estimate">
                        This is a planning calculation based on the rates entered here. Always compare it with the current Programme Guide and the final amount confirmed by the National Agency.
                    </x-help-tip>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.2rem;">
                    @if($canManage)
                        <span wire:loading.remove>Changes are saved automatically</span><span wire:loading style="color:#6366f1;">Saving estimate…</span>
                    @else
                        Read-only access
                    @endif
                </p>
            </div>
            <x-filament::badge color="gray">Writing stage</x-filament::badge>
        </div>
    </x-filament::section>

    <div class="mc-est-grid">
    <x-filament::section heading="Inputs">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;">
            <div>
                <div class="fl">
                    <label class="f">Persons (IS + travel)</label>
                    <x-help-tip id="estimate-is-persons" title="Persons for IS and travel">
                        People eligible for Individual Support and the travel unit contribution. This can differ from the Organisational Support participant count under some action rules.
                    </x-help-tip>
                </div>
                <input type="number" min="0" wire:model.live.debounce.500ms="persons" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <div class="fl">
                    <label class="f">Participants (OS)</label>
                    <x-help-tip id="estimate-os-participants" title="Participants for Organisational Support">
                        Enter the number of people who generate an Organisational Support unit contribution under the applicable programme rules.
                    </x-help-tip>
                </div>
                <input type="number" min="0" wire:model.live.debounce.500ms="participants" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <label class="f">Activity days</label>
                <input type="number" min="0" wire:model.live.debounce.500ms="days" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <label class="f">Travel days</label>
                <input type="number" min="0" wire:model.live.debounce.500ms="travelDays" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <label class="f">IS rate (€/person/day)</label>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="isRate" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <label class="f">Distance band</label>
                <select wire:model.live="travelBandIndex" class="text-gray-950 dark:text-white" style="{{ $inStyle }}" @disabled(!$canManage)>
                    @foreach($bands as $i => $b)
                        <option value="{{ $i }}">{{ $b['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="f">OS rate (€/participant)</label>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="osRate" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
            <div>
                <div class="fl">
                    <label class="f">Inclusion support — organisations (€)</label>
                    <x-help-tip id="estimate-inclusion-support" title="Inclusion Support for organisations">
                        Enter the approved unit contribution or total intended to help the organisation arrange participation for people with fewer opportunities. Participant-specific real costs are handled separately when applicable.
                    </x-help-tip>
                </div>
                <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="inclusionOrgTotal" style="{{ $inStyle }}" @disabled(!$canManage)>
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-top:1rem;">
            <label class="row"><input type="checkbox" wire:model.live="greenTravel" @disabled(!$canManage)> Green travel</label>
            <label class="row"><input type="checkbox" wire:model.live="includeTravelDaysInIS" @disabled(!$canManage)> Include travel days in IS</label>
            <label class="row"><input type="checkbox" wire:model.live="includeOS" @disabled(!$canManage)> Include Organisational Support</label>
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

            <div class="mc-est-total" style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;">
                <span style="font-weight:700;font-size:1rem;">Total grant</span>
                <span style="font-weight:700;font-size:1.4rem;">{{ $eur($this->grandTotal) }}</span>
            </div>
        </div>

        <div style="margin-top:1rem;padding:.7rem .9rem;border-radius:8px;background:rgba(99,102,241,.08);font-size:.8rem;line-height:1.5;">
            These figures are saved automatically and pre-fill the grant and budget baskets when you mark the project as <strong>Approved</strong> from the Overview tab. You can adjust the confirmed grant afterwards in Settings.
        </div>
    </x-filament::section>
    </div>
    </div>
</x-filament-panels::page>
