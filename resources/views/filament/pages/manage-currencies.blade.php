<x-filament-panels::page>

    <style>
        .mc-cur input[type=number]::-webkit-outer-spin-button,
        .mc-cur input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        .mc-cur input[type=number] { -moz-appearance:textfield; appearance:textfield; }
    </style>

    <div class="mc-cur" style="max-width:640px;">

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.25rem;margin-bottom:1.25rem;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0 0 1rem;">
                Base currency is <strong class="text-gray-950 dark:text-white">EUR</strong>. Add other currencies and set how many units equal 1 EUR.
                Example: if 1 EUR = 5.07 RON, set rate <strong>5.07</strong>.
            </p>

            {{-- EUR base row --}}
            <div style="display:flex;align-items:center;gap:1rem;padding:8px 0;border-bottom:1px solid rgba(100,116,139,.12);">
                <span class="text-gray-950 dark:text-white" style="font-weight:600;width:80px;font-family:monospace;">EUR</span>
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">Base currency · rate 1.00</span>
            </div>

            {{-- Currency rows --}}
            @forelse($rows as $i => $row)
            <div style="display:flex;align-items:center;gap:1rem;padding:10px 0;border-bottom:1px solid rgba(100,116,139,.08);">
                <span class="text-gray-950 dark:text-white" style="font-weight:600;width:80px;font-family:monospace;">{{ $row['code'] }}</span>
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">1 EUR =</span>
                <input type="number" step="0.0001" min="0" value="{{ $row['rate'] }}"
                       wire:change="updateRate({{ $i }}, $event.target.value)"
                       class="text-gray-950 dark:text-white"
                       style="width:120px;text-align:right;background:transparent;border:1px solid rgba(100,116,139,.3);border-radius:6px;padding:6px 10px;font-size:14px;">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">{{ $row['code'] }}</span>
                <button type="button" wire:click="removeCurrency({{ $i }})" wire:confirm="Remove {{ $row['code'] }}?"
                        style="margin-left:auto;width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;"
                        onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
            @empty
            <p class="text-gray-400" style="font-size:13px;font-style:italic;padding:10px 0;">No extra currencies yet. EUR only.</p>
            @endforelse
        </div>

        {{-- Add new --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1.25rem;">
            <p class="text-gray-950 dark:text-white" style="font-weight:600;font-size:13px;margin:0 0 .75rem;">Add currency</p>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <input type="text" wire:model="newCode" maxlength="5" placeholder="RON"
                       class="text-gray-950 dark:text-white"
                       style="width:90px;text-transform:uppercase;background:transparent;border:1px solid rgba(100,116,139,.3);border-radius:6px;padding:8px 10px;font-size:14px;font-family:monospace;">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">1 EUR =</span>
                <input type="number" step="0.0001" min="0" wire:model="newRate" placeholder="5.07"
                       class="text-gray-950 dark:text-white"
                       style="width:120px;text-align:right;background:transparent;border:1px solid rgba(100,116,139,.3);border-radius:6px;padding:8px 10px;font-size:14px;">
                <button type="button" wire:click="addCurrency"
                        style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">Add</button>
            </div>
        </div>

    </div>

</x-filament-panels::page>
