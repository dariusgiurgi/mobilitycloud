<x-filament-panels::page>
    <style>
        .mc-calc input[type=number]::-webkit-outer-spin-button,
        .mc-calc input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        .mc-calc input[type=number] { -moz-appearance:textfield; appearance:textfield; }
        .dark .mc-calc select option { background:#27303f; color:#f4f4f5; }
        .dark .mc-calc select { color:#f4f4f5; }
        .mc-inp { background:transparent; border:1px solid rgba(100,116,139,.3); border-radius:6px; padding:7px 10px; font-size:14px; width:100%; }
        .mc-lbl { display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
    </style>

    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-bottom:1rem;">
        <a href="{{ route('calc.export', ['type' => 'is']) }}?participants={{ $participants }}&days={{ $days }}&isRate={{ $isRate }}&travelDays={{ $travelDays }}&isTravelDaysIncluded={{ $isTravelDaysIncluded ? 1 : 0 }}&travelBandIndex={{ $travelBandIndex }}&greenTravel={{ $greenTravel ? 1 : 0 }}&osRate={{ $osRate }}&includeOS={{ $includeOS ? 1 : 0 }}"
           target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;font-weight:500;text-decoration:none;"
           class="text-gray-700 dark:text-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
            Export PDF
        </a>
        <button type="button" wire:click="openSave"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
            Save
        </button>
    </div>

    <div class="mc-calc" style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;">

        {{-- ═══ LEFT: Inputs ═══ --}}
        <div style="display:flex;flex-direction:column;gap:1rem;">

            {{-- Shared --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.25rem;">
                <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;margin:0 0 1rem;">General</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <label class="mc-lbl text-gray-500 dark:text-gray-400">Participants</label>
                        <input type="number" min="1" wire:model.live="participants" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                    <div>
                        <label class="mc-lbl text-gray-500 dark:text-gray-400">Activity days</label>
                        <input type="number" min="1" wire:model.live="days" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Individual Support --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="border-left:4px solid #22c55e;padding:1.25rem;">
                <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;margin:0 0 1rem;">🙋 Individual Support</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <label class="mc-lbl text-gray-500 dark:text-gray-400">Rate (€/day/participant)</label>
                        <input type="number" step="0.01" min="0" wire:model.live="isRate" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                    <div>
                        <label class="mc-lbl text-gray-500 dark:text-gray-400">Travel days (IS)</label>
                        <input type="number" min="0" wire:model.live="travelDays" class="mc-inp text-gray-950 dark:text-white">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:.75rem;font-size:13px;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="isTravelDaysIncluded" style="width:15px;height:15px;accent-color:#6366f1;">
                    Include travel days in IS
                </label>
            </div>

            {{-- Travel --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="border-left:4px solid #3b82f6;padding:1.25rem;">
                <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;margin:0 0 1rem;">✈️ Travel</p>
                <label class="mc-lbl text-gray-500 dark:text-gray-400">Distance band (one-way)</label>
                <select wire:model.live="travelBandIndex" class="mc-inp text-gray-950 dark:text-white">
                    @foreach($this::TRAVEL_BANDS as $i => $band)
                        <option value="{{ $i }}">{{ $band['label'] }} — € {{ $greenTravel ? $band['green'] : $band['standard'] }}/participant</option>
                    @endforeach
                </select>
                <label style="display:flex;align-items:center;gap:8px;margin-top:.75rem;font-size:13px;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="greenTravel" style="width:15px;height:15px;accent-color:#22c55e;">
                    Green travel (higher rate)
                </label>
            </div>

            {{-- Organisational Support --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="border-left:4px solid #8b5cf6;padding:1.25rem;">
                <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;margin:0 0 1rem;">🏢 Organisational Support</p>
                <label class="mc-lbl text-gray-500 dark:text-gray-400">Rate (€/participant)</label>
                <input type="number" step="0.01" min="0" wire:model.live="osRate" class="mc-inp text-gray-950 dark:text-white" style="max-width:200px;">
                <label style="display:flex;align-items:center;gap:8px;margin-top:.75rem;font-size:13px;cursor:pointer;" class="text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="includeOS" style="width:15px;height:15px;accent-color:#6366f1;">
                    Include Organisational Support
                </label>
            </div>

        </div>

        {{-- ═══ RIGHT: Results ═══ --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.25rem;position:sticky;top:1rem;">
            <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;margin:0 0 1rem;">Summary</p>

            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(100,116,139,.12);">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">Individual Support</span>
                <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">€ {{ number_format($this->isTotal, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(100,116,139,.12);">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">Travel</span>
                <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">€ {{ number_format($this->travelTotal, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(100,116,139,.12);">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">Organisational Support</span>
                <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">€ {{ number_format($this->osTotal, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:14px 0 4px;">
                <span class="text-gray-950 dark:text-white" style="font-weight:700;font-size:15px;">Total</span>
                <span style="font-weight:700;font-size:20px;color:#6366f1;">€ {{ number_format($this->grandTotal, 2) }}</span>
            </div>

            <div class="text-gray-400" style="font-size:11px;margin-top:1rem;line-height:1.5;">
                {{ $participants }} participant(s) × {{ $days }}@if($isTravelDaysIncluded)+{{ $travelDays }}@endif days.
                Travel: {{ $this::TRAVEL_BANDS[$travelBandIndex]['label'] }}{{ $greenTravel ? ' (green)' : '' }}.
            </div>
        </div>

    </div>

    <p class="text-gray-400" style="font-size:11px;margin-top:1rem;max-width:760px;line-height:1.5;">
        Travel rates based on Erasmus+ Call 2024/2025 distance bands. Individual Support and Organisational Support rates vary by action type, country and National Agency — adjust the default rates above to match your specific call.
    </p>

    {{-- Saved calculations --}}
    @if($this->saved->count() > 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-top:1.5rem;overflow:hidden;">
        <div style="padding:.85rem 1.1rem;border-bottom:1px solid rgba(100,116,139,.12);">
            <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">Saved calculations</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:600px;">
                <thead>
                    <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:10px;text-transform:uppercase;letter-spacing:.04em;">
                        <th style="padding:8px 10px;text-align:left;">Name</th>
                        <th style="padding:8px 10px;text-align:left;">Date</th>
                        <th style="padding:8px 10px;text-align:right;">Total</th>
                        <th style="padding:8px 10px;text-align:center;width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->saved as $calc)
                    <tr class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                        <td style="padding:8px 10px;font-weight:500;">{{ $calc->name }}</td>
                        <td style="padding:8px 10px;" class="text-gray-500 dark:text-gray-400">{{ $calc->created_at->format('d M Y, H:i') }}</td>
                        <td style="padding:8px 10px;text-align:right;font-weight:600;">€ {{ number_format($calc->results['total'] ?? 0, 2) }}</td>
                        <td style="padding:8px 10px;text-align:center;">
                            <button type="button" wire:click="loadCalculation({{ $calc->id }})" title="Load"
                                    style="padding:4px 10px;border-radius:6px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:11px;margin-right:4px;" class="text-gray-700 dark:text-gray-200">Load</button>
                            <button type="button" wire:click="deleteCalculation({{ $calc->id }})" wire:confirm="Delete this saved calculation?" title="Delete"
                                    style="width:26px;height:26px;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;vertical-align:middle;"
                                    onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                                    onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Save modal --}}
    @if($showSaveModal)
    <div style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;padding:1rem;" wire:click.self="$set('showSaveModal', false)">
        <div style="width:100%;max-width:400px;border-radius:14px;padding:1.5rem;box-shadow:0 20px 50px rgba(0,0,0,.4);background:#ffffff;" class="mc-save-modal">
            <h3 style="font-size:16px;font-weight:600;margin:0 0 1rem;" class="mc-smt">Save calculation</h3>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:5px;" class="mc-sml">Name</label>
            <input type="text" wire:model="saveName" placeholder="e.g. Youth Exchange Italy 2026"
                   class="mc-smi" style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;font-size:14px;margin-bottom:.5rem;">
            @error('saveName') <p style="color:#dc2626;font-size:12px;margin:0 0 .5rem;">{{ $message }}</p> @enderror
            <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;">
                <button type="button" wire:click="$set('showSaveModal', false)" class="mc-smc" style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                <button type="button" wire:click="saveCalculation" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">Save</button>
            </div>
        </div>
    </div>
    @endif

    <style>
        .mc-save-modal { background:#fff; } .mc-smt { color:#18181b; } .mc-sml { color:#71717a; }
        .mc-smi { background:#fafafa; color:#18181b; } .mc-smc { color:#3f3f46; }
        .dark .mc-save-modal { background:#18212f !important; } .dark .mc-smt { color:#f4f4f5 !important; }
        .dark .mc-sml { color:#a1a1aa !important; } .dark .mc-smi { background:#27303f !important; color:#f4f4f5 !important; }
        .dark .mc-smc { color:#d4d4d8 !important; }
    </style>

</x-filament-panels::page>
