<x-filament-panels::page>
    <x-ui-polish />
    @php
        $band = $this::TRAVEL_BANDS[$travelBandIndex] ?? $this::TRAVEL_BANDS[0];
        $canManage = auth()->check() && $this::canAccess() && ! \App\Support\PlatformAccess::isReadOnly();
        $exportUrl = route('calc.export', ['type' => 'is']).'?'.http_build_query([
            'participants' => $participants,
            'days' => $days,
            'isRate' => $isRate,
            'travelDays' => $travelDays,
            'isTravelDaysIncluded' => $isTravelDaysIncluded ? 1 : 0,
            'travelBandIndex' => $travelBandIndex,
            'greenTravel' => $greenTravel ? 1 : 0,
            'osRate' => $osRate,
            'includeOS' => $includeOS ? 1 : 0,
        ]);
    @endphp

    <style>
        .mc-calc input[type=number]::-webkit-outer-spin-button,.mc-calc input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none;margin:0; }
        .mc-calc input[type=number] { -moz-appearance:textfield;appearance:textfield; }
        .mc-calc-layout { display:grid;grid-template-columns:minmax(0,1fr) 350px;gap:1.15rem;align-items:start; }
        .mc-calc-stack { display:grid;gap:1rem; }
        .mc-calc-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
        .mc-inp { width:100%;padding:.55rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.5rem;background:transparent;font-size:.875rem; }
        .mc-label-row { display:flex;align-items:center;gap:.35rem;margin-bottom:.4rem; }
        .mc-lbl { font-size:.7rem;font-weight:650;text-transform:uppercase;letter-spacing:.04em; }
        .mc-calc-note { color:#64748b;font-size:.76rem;line-height:1.5; }
        .mc-formula { margin-top:.7rem;padding:.65rem .75rem;border-radius:.55rem;background:rgba(100,116,139,.07);color:#64748b;font-size:.73rem;line-height:1.45; }
        .mc-result-row { display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:.75rem 0;border-bottom:1px solid rgba(100,116,139,.14); }
        .mc-result-row:last-child { border-bottom:0; }
        .dark .mc-calc select option { background:#27303f;color:#f4f4f5; }
        .dark .mc-inp { color:#f4f4f5; }
        .dark .mc-calc-note,.dark .mc-formula { color:#94a3b8; }
        .dark .mc-formula { background:rgba(255,255,255,.05); }
        @media (max-width:1024px) { .mc-calc-layout { grid-template-columns:1fr; } .mc-calc-summary { position:static !important; } }
        @media (max-width:640px) { .mc-calc-grid { grid-template-columns:1fr; } }
    </style>

    <x-filament::section>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="max-width:720px;">
                <div style="display:flex;align-items:center;gap:.45rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:650;">Planning estimate</h2>
                    <x-help-tip id="calculator-scope" title="What this calculator includes">
                        It estimates three common Erasmus+ unit contributions: Individual Support, Travel and Organisational Support. It does not determine eligibility or replace the rates in your call or grant agreement.
                    </x-help-tip>
                </div>
                <p class="mc-calc-note" style="margin-top:.3rem;">Enter the eligible people, days and unit rates from your programme guide. The calculation updates immediately.</p>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <x-filament::button tag="a" :href="$exportUrl" target="_blank" color="gray" icon="heroicon-o-document-arrow-down" size="sm">
                    Export PDF
                </x-filament::button>
                @if ($canManage)
                    <x-filament::button wire:click="openSave" icon="heroicon-o-bookmark" size="sm">
                        Save calculation
                    </x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

    <div class="mc-calc mc-calc-layout">
        <div class="mc-calc-stack">
            <x-filament::section heading="1. People and duration" description="Define the common basis for the estimate.">
                <div class="mc-calc-grid">
                    <div>
                        <div class="mc-label-row">
                            <label for="calc-participants" class="mc-lbl text-gray-500 dark:text-gray-400">Eligible participants</label>
                            <x-help-tip id="participants" title="Eligible participants">
                                Count only people eligible for the selected contribution. If groups have different durations or rates, calculate them separately and save each scenario.
                            </x-help-tip>
                        </div>
                        <input id="calc-participants" type="number" min="1" wire:model.live="participants" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                    <div>
                        <div class="mc-label-row">
                            <label for="calc-days" class="mc-lbl text-gray-500 dark:text-gray-400">Activity days</label>
                            <x-help-tip id="activity-days" title="Activity days">
                                The funded programme days for each participant, excluding travel days. Use the duration accepted by the programme rules, not simply the hotel nights.
                            </x-help-tip>
                        </div>
                        <input id="calc-days" type="number" min="1" wire:model.live="days" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="2. Individual Support" description="Daily unit contribution for eligible participants.">
                <div class="mc-calc-grid">
                    <div>
                        <div class="mc-label-row">
                            <label for="calc-is-rate" class="mc-lbl text-gray-500 dark:text-gray-400">Daily rate (€ / person)</label>
                            <x-help-tip id="is-rate" title="Individual Support rate">
                                A daily unit contribution commonly used for accommodation, meals and local costs. The correct amount depends on the action, destination and applicable call.
                            </x-help-tip>
                        </div>
                        <input id="calc-is-rate" type="number" step="0.01" min="0" wire:model.live="isRate" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                    <div>
                        <div class="mc-label-row">
                            <label for="calc-travel-days" class="mc-lbl text-gray-500 dark:text-gray-400">Eligible travel days</label>
                            <x-help-tip id="travel-days" title="Travel days with Individual Support">
                                Some actions allow Individual Support for a limited number of travel days. Enter only the days permitted by your programme rules; enter 0 when none are eligible.
                            </x-help-tip>
                        </div>
                        <input id="calc-travel-days" type="number" min="0" wire:model.live="travelDays" class="mc-inp text-gray-950 dark:text-white" @disabled(! $isTravelDaysIncluded)>
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:.5rem;margin-top:.8rem;font-size:.8rem;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="isTravelDaysIncluded" style="width:15px;height:15px;accent-color:#6366f1;">
                    Include eligible travel days in Individual Support
                </label>
                <div class="mc-formula">
                    {{ $participants }} people × {{ $this->eligibleDays }} eligible days × {{ number_format($isRate, 2) }} € = <strong>{{ number_format($this->isTotal, 2) }} €</strong>
                </div>
            </x-filament::section>

            <x-filament::section heading="3. Travel grant" description="Unit contribution based on travel distance and travel type.">
                <div class="mc-label-row">
                    <label for="calc-distance" class="mc-lbl text-gray-500 dark:text-gray-400">One-way distance band</label>
                    <x-help-tip id="distance-band" title="Distance band">
                        Use the programme distance calculator and select the one-way straight-line distance between the place of origin and the activity venue. The resulting unit contribution normally supports the return journey.
                    </x-help-tip>
                </div>
                <select id="calc-distance" wire:model.live="travelBandIndex" class="mc-inp text-gray-950 dark:text-white">
                    @foreach ($this::TRAVEL_BANDS as $index => $travelBand)
                        <option value="{{ $index }}">{{ $travelBand['label'] }} — {{ number_format($greenTravel ? $travelBand['green'] : $travelBand['standard'], 2) }} € / person</option>
                    @endforeach
                </select>
                <label style="display:flex;align-items:center;gap:.5rem;margin-top:.8rem;font-size:.8rem;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="greenTravel" style="width:15px;height:15px;accent-color:#22c55e;">
                    Green travel rate
                    <x-help-tip id="green-travel" title="Green travel">
                        Select this only when the main part of the journey uses eligible lower-emission transport under your programme rules. Check the current guide before applying the higher rate.
                    </x-help-tip>
                </label>
                <div class="mc-formula">
                    {{ $participants }} people × {{ number_format($this->travelPerParticipant, 2) }} € for {{ $band['label'] }}{{ $greenTravel ? ' green travel' : '' }} = <strong>{{ number_format($this->travelTotal, 2) }} €</strong>
                </div>
            </x-filament::section>

            <x-filament::section heading="4. Organisational Support" description="Contribution for preparing and delivering the activity.">
                <div style="max-width:260px;">
                    <div class="mc-label-row">
                        <label for="calc-os-rate" class="mc-lbl text-gray-500 dark:text-gray-400">Rate (€ / person)</label>
                        <x-help-tip id="os-rate" title="Organisational Support">
                            A unit contribution for preparation, implementation and follow-up by the organisation. It is separate from the amount intended for each participant's daily costs.
                        </x-help-tip>
                    </div>
                    <input id="calc-os-rate" type="number" step="0.01" min="0" wire:model.live="osRate" class="mc-inp text-gray-950 dark:text-white" @disabled(! $includeOS)>
                </div>
                <label style="display:flex;align-items:center;gap:.5rem;margin-top:.8rem;font-size:.8rem;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="includeOS" style="width:15px;height:15px;accent-color:#6366f1;">
                    Include Organisational Support
                </label>
                <div class="mc-formula">
                    @if ($includeOS)
                        {{ $participants }} people × {{ number_format($osRate, 2) }} € = <strong>{{ number_format($this->osTotal, 2) }} €</strong>
                    @else
                        Organisational Support is excluded from this estimate.
                    @endif
                </div>
            </x-filament::section>
        </div>

        <div class="mc-calc-summary" style="position:sticky;top:1rem;">
            <x-filament::section heading="Estimate summary" description="Calculated from the values on the left.">
                <div class="mc-result-row">
                    <div>
                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:600;">Individual Support</span>
                        <span class="mc-calc-note">{{ $participants }} × {{ $this->eligibleDays }} × {{ number_format($isRate, 2) }} €</span>
                    </div>
                    <strong class="text-gray-950 dark:text-white" style="font-size:.86rem;white-space:nowrap;">{{ number_format($this->isTotal, 2) }} €</strong>
                </div>
                <div class="mc-result-row">
                    <div>
                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:600;">Travel grant</span>
                        <span class="mc-calc-note">{{ $band['label'] }}{{ $greenTravel ? ' · green' : '' }}</span>
                    </div>
                    <strong class="text-gray-950 dark:text-white" style="font-size:.86rem;white-space:nowrap;">{{ number_format($this->travelTotal, 2) }} €</strong>
                </div>
                <div class="mc-result-row">
                    <div>
                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:600;">Organisational Support</span>
                        <span class="mc-calc-note">{{ $includeOS ? $participants.' × '.number_format($osRate, 2).' €' : 'Not included' }}</span>
                    </div>
                    <strong class="text-gray-950 dark:text-white" style="font-size:.86rem;white-space:nowrap;">{{ number_format($this->osTotal, 2) }} €</strong>
                </div>
                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;padding-top:1rem;">
                    <div>
                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.9rem;font-weight:700;">Estimated total</span>
                        <span class="mc-calc-note">Planning figure</span>
                    </div>
                    <strong style="font-size:1.4rem;color:#6366f1;white-space:nowrap;">{{ number_format($this->grandTotal, 2) }} €</strong>
                </div>
            </x-filament::section>

            <div style="margin-top:.75rem;padding:.8rem;border:1px solid rgba(245,158,11,.3);border-radius:.65rem;background:rgba(245,158,11,.07);font-size:.73rem;line-height:1.5;color:#92400e;" class="dark:text-amber-300">
                <strong>Verify before using:</strong> rates and eligibility vary by action, country, call year and National Agency. Treat this as a planning estimate, not a grant decision.
            </div>
        </div>
    </div>

    @if ($this->saved->count() > 0)
        <x-filament::section heading="Saved calculations" description="Load a previous scenario without changing the project budget." style="margin-top:1.5rem;">
            <div class="mc-table-scroll" style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.78rem;min-width:600px;">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;">
                            <th style="padding:.55rem .65rem;text-align:left;">Name</th>
                            <th style="padding:.55rem .65rem;text-align:left;">Saved</th>
                            <th style="padding:.55rem .65rem;text-align:right;">Total</th>
                            <th style="padding:.55rem .65rem;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->saved as $calculation)
                            <tr class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                                <td style="padding:.65rem;font-weight:550;">{{ $calculation->name }}</td>
                                <td style="padding:.65rem;" class="text-gray-500 dark:text-gray-400">{{ $calculation->created_at->format('d M Y, H:i') }}</td>
                                <td style="padding:.65rem;text-align:right;font-weight:650;">{{ number_format($calculation->results['total'] ?? 0, 2) }} €</td>
                                <td style="padding:.65rem;text-align:right;white-space:nowrap;">
                                    <x-filament::button wire:click="loadCalculation({{ $calculation->id }})" color="gray" size="xs">Load</x-filament::button>
                                    @if ($canManage)
                                        <x-filament::icon-button wire:click="deleteCalculation({{ $calculation->id }})" wire:confirm="Delete this saved calculation?" icon="heroicon-o-trash" color="danger" size="sm" label="Delete calculation" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @if ($showSaveModal)
        <div class="mc-modal-backdrop" wire:click.self="$set('showSaveModal', false)">
            <div class="mc-modal-panel" style="max-width:400px;">
                <div class="mc-modal-body">
                    <h3 class="mc-modal-heading">Save calculation</h3>
                    <p class="mc-modal-description">Give this scenario a clear name so your team can recognise and reuse it later.</p>
                    <label for="calc-save-name" class="mc-lbl text-gray-500 dark:text-gray-400">Name</label>
                    <input id="calc-save-name" type="text" wire:model="saveName" placeholder="e.g. Youth Exchange Italy 2026" class="mc-inp text-gray-950 dark:text-white" style="margin-top:.35rem;">
                    @error('saveName') <p style="color:#dc2626;font-size:.75rem;margin-top:.35rem;">{{ $message }}</p> @enderror
                    <div class="mc-modal-actions">
                        <x-filament::button wire:click="$set('showSaveModal', false)" color="gray" size="sm">Cancel</x-filament::button>
                        <x-filament::button wire:click="saveCalculation" size="sm">Save</x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
