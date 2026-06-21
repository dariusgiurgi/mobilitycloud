<x-filament-panels::page>
    @php
        $canManage = \Filament\Facades\Filament::getTenant()?->canBeManagedBy(auth()->user()) ?? false;
    @endphp

    <style>
        .mc-cur input[type=number]::-webkit-outer-spin-button,.mc-cur input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none;margin:0; }
        .mc-cur input[type=number] { -moz-appearance:textfield;appearance:textfield; }
        .mc-cur-input { padding:.5rem .65rem;border:1px solid rgba(100,116,139,.3);border-radius:.5rem;background:transparent;font-size:.85rem; }
        .mc-cur-row { display:grid;grid-template-columns:70px minmax(220px,1fr) minmax(170px,.8fr) 36px;gap:1rem;align-items:center;padding:.85rem 0;border-top:1px solid rgba(100,116,139,.12); }
        .mc-cur-add { display:grid;grid-template-columns:100px minmax(180px,260px) auto;gap:.75rem;align-items:start; }
        .mc-cur-muted { color:#64748b; }
        .dark .mc-cur-muted { color:#94a3b8; }
        @media (max-width:700px) { .mc-cur-row { grid-template-columns:60px 1fr 36px;gap:.65rem; } .mc-cur-inverse { grid-column:2 / 3; } .mc-cur-add { grid-template-columns:1fr; } .mc-cur-add .fi-btn { margin-top:0 !important; } }
    </style>

    <div class="mc-cur" style="max-width:780px;display:grid;gap:1rem;">
        <x-filament::section>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div style="max-width:600px;">
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">EUR is the base currency</h2>
                        <x-help-tip id="currency-rate-direction" title="How to enter a rate">
                            Enter how many units of the foreign currency equal 1 EUR. For example, when 1 EUR equals 5.07 RON, enter 5.07—not 0.1972.
                        </x-help-tip>
                    </div>
                    <p class="mc-cur-muted" style="font-size:.78rem;line-height:1.5;margin-top:.3rem;">Every non-EUR expense is divided by its configured rate to obtain the EUR value used in project budgets and reports.</p>
                </div>
                <x-filament::badge color="primary">1 EUR = 1.00 EUR</x-filament::badge>
            </div>

            <div style="display:flex;align-items:flex-start;gap:.55rem;margin-top:1rem;padding:.75rem;border-radius:.6rem;background:rgba(245,158,11,.08);color:#92400e;font-size:.74rem;line-height:1.5;" class="dark:text-amber-300">
                <x-filament::icon icon="heroicon-o-information-circle" style="width:1rem;height:1rem;flex:none;margin-top:.1rem;" />
                <span>
                    Rates are entered manually and are not updated from an external exchange-rate service.
                    Changing a rate affects newly entered or edited conversions; existing expense values are not recalculated automatically.
                </span>
                <x-help-tip id="currency-rate-application" title="When rates are applied">
                    MobilityCloud stores the EUR amount on each expense. After changing a workspace rate, edit an existing expense amount or currency if you intentionally want to recalculate that expense.
                </x-help-tip>
            </div>
        </x-filament::section>

        <x-filament::section heading="Configured currencies" description="Rates are shared by every project in this workspace.">
            <div class="mc-cur-row" style="border-top:0;padding-top:0;">
                <strong class="text-gray-950 dark:text-white" style="font-family:monospace;">EUR</strong>
                <div>
                    <span class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:600;">1 EUR = 1.000000 EUR</span>
                    <span class="mc-cur-muted" style="display:block;font-size:.7rem;margin-top:.15rem;">Base currency · cannot be removed</span>
                </div>
                <span class="mc-cur-muted mc-cur-inverse" style="font-size:.74rem;">1 EUR = 1.000000 EUR</span>
                <span></span>
            </div>

            @foreach ($rows as $index => $row)
                <div class="mc-cur-row" wire:key="currency-{{ $row['code'] }}">
                    <strong class="text-gray-950 dark:text-white" style="font-family:monospace;">{{ $row['code'] }}</strong>
                    <div>
                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                            <span class="mc-cur-muted" style="font-size:.78rem;">1 EUR =</span>
                            @if ($canManage)
                                <input
                                    type="number"
                                    min="0.000001"
                                    max="1000000"
                                    step="0.000001"
                                    value="{{ $row['rate'] }}"
                                    aria-label="Exchange rate for {{ $row['code'] }}"
                                    wire:change="updateRate({{ $index }}, $event.target.value)"
                                    class="mc-cur-input text-gray-950 dark:text-white"
                                    style="width:135px;text-align:right;"
                                >
                            @else
                                <strong class="text-gray-950 dark:text-white" style="font-size:.84rem;">{{ number_format((float) $row['rate'], 6, '.', '') }}</strong>
                            @endif
                            <span class="mc-cur-muted" style="font-size:.78rem;">{{ $row['code'] }}</span>
                        </div>
                        @error('rows.'.$index.'.rate')
                            <p style="color:#dc2626;font-size:.72rem;margin-top:.3rem;">{{ $message }}</p>
                        @enderror
                    </div>
                    <span class="mc-cur-muted mc-cur-inverse" style="font-size:.74rem;">1 {{ $row['code'] }} = {{ number_format(1 / (float) $row['rate'], 6, '.', '') }} EUR</span>
                    @if ($canManage)
                        <x-filament::icon-button
                            wire:click="removeCurrency({{ $index }})"
                            wire:confirm="Remove {{ $row['code'] }} from this workspace? Existing expenses will keep their stored EUR values."
                            icon="heroicon-o-trash"
                            color="danger"
                            size="sm"
                            label="Remove {{ $row['code'] }}"
                        />
                    @else
                        <span></span>
                    @endif
                </div>
            @endforeach

            @if ($rows === [])
                <div style="padding:1.2rem 0 .4rem;text-align:center;">
                    <p class="text-gray-950 dark:text-white" style="font-size:.84rem;font-weight:600;">EUR only</p>
                    <p class="mc-cur-muted" style="font-size:.75rem;margin-top:.2rem;">Add another currency when a project records non-EUR expenses.</p>
                </div>
            @endif
        </x-filament::section>

        @if ($canManage)
            <x-filament::section heading="Add currency" description="Use the standard three-letter currency code.">
                <div class="mc-cur-add">
                    <div>
                        <label for="new-currency-code" class="mc-cur-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.35rem;">Code</label>
                        <input id="new-currency-code" type="text" wire:model="newCode" maxlength="3" placeholder="RON" class="mc-cur-input text-gray-950 dark:text-white" style="width:100%;text-transform:uppercase;font-family:monospace;">
                        @error('newCode') <p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="new-currency-rate" class="mc-cur-muted" style="display:block;font-size:.68rem;font-weight:650;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.35rem;">Units for 1 EUR</label>
                        <input id="new-currency-rate" type="number" step="0.000001" min="0.000001" max="1000000" wire:model="newRate" placeholder="5.070000" class="mc-cur-input text-gray-950 dark:text-white" style="width:100%;text-align:right;">
                        @error('newRate') <p style="color:#dc2626;font-size:.7rem;margin-top:.3rem;">{{ $message }}</p> @enderror
                    </div>
                    <x-filament::button wire:click="addCurrency" wire:loading.attr="disabled" wire:target="addCurrency" icon="heroicon-o-plus" style="margin-top:1.25rem;">
                        Add currency
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
